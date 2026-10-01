<?php
// Uses only randomly named schemas on a disposable localhost MySQL instance.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('zend.exception_ignore_args', '1');
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$port = getenv('PK_TEST_PORT');
if (!$port) { throw new RuntimeException('Set PK_TEST_PORT to a disposable localhost MySQL server.'); }
$config = ['driver'=>'mysql', 'host'=>'127.0.0.1', 'port'=>(int)$port,
    'username'=>getenv('PK_TEST_USER') ?: 'root', 'password'=>getenv('PK_TEST_PASSWORD') ?: '',
    'database'=>'', 'charset'=>'utf8mb4', 'collation'=>'utf8mb4_unicode_ci', 'prefix'=>'', 'strict'=>true];
config(['database.connections.pk_test'=>$config, 'database.default'=>'pk_test']);
$connection = DB::connection('pk_test');
$schema = 'pk_verify_'.bin2hex(random_bytes(6));
$other = $schema.'_other';
$checks = 0;
function checkPk($condition, $message) {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
$migration = require __DIR__.'/../database/migrations/2026_10_01_000001_add_primary_keys_to_legacy_tables.php';
$fixtures = [
    'click_geo'=>['click_id'=>1,'iso_code'=>'US','postal'=>'12345','ip'=>'127.0.0.1'],
    'click_has_bonus'=>['click_bonus_id'=>1,'click_id'=>1],
    'click_vars'=>['click_id'=>1,'url'=>'https://example.test'],
    'offer_caps'=>['offer_idoffer'=>1,'type'=>1,'time_interval'=>1,'interval_cap'=>10,'redirect_offer'=>1],
    'referrals'=>['referrer_user_id'=>1,'aff_id'=>1,'start_date'=>'2026-01-01','referral_type'=>'Flat Fee','payout'=>1],
    'user_has_bonus'=>['bonus_id'=>1,'user_id'=>1],
    'user_has_notification'=>['notification_id'=>1,'user_id'=>1],
];
try {
    $connection->statement("CREATE DATABASE `{$schema}`");
    $connection->statement("CREATE DATABASE `{$other}`");
    $connection->statement("CREATE TABLE `{$other}`.untouched (value INT)");
    $connection->statement("USE `{$schema}`");
    $connection->setDatabaseName($schema);
    $connection->statement('SET FOREIGN_KEY_CHECKS=0');
    preg_match_all('/CREATE TABLE `[^`]+` \(.*?\) ENGINE[^;]+;/s', file_get_contents(__DIR__.'/../base_install.sql'), $matches);
    foreach ($matches[0] as $sql) {
        $connection->unprepared(str_replace("  `_replication_id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,\n", '', $sql));
    }
    $before = [];
    foreach ($fixtures as $table=>$row) {
        $connection->table($table)->insert([$row, $row]);
        $before[$table] = $connection->table($table)->get()->map(fn($r)=>(array)$r)->all();
    }
    // Foreign key definitions must survive the rebuilds.
    $foreignKeys = $connection->select("SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' ORDER BY TABLE_NAME, CONSTRAINT_NAME", [$schema]);
    checkPk(Artisan::call('db:audit-primary-keys', ['--database'=>'pk_test']) === 1, 'Audit must detect missing keys');
    checkPk(str_contains(Artisan::output(), 'click_vars'), 'Audit must identify affected tables');
    $connection->statement('SET SESSION lock_wait_timeout = 37');
    $migration->up();
    checkPk((int)$connection->selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')->seconds === 37, 'Lock timeout must be restored');
    foreach ($fixtures as $table=>$row) {
        $after = $connection->table($table)->orderBy('_replication_id')->get()->map(function($r) { $r=(array)$r; unset($r['_replication_id']); return $r; })->all();
        checkPk($after === $before[$table], "Data changed in {$table}");
        $connection->table($table)->insert($row);
        checkPk($connection->table($table)->distinct()->count('_replication_id') === 3, "Automatic unique IDs missing in {$table}");
    }
    checkPk($foreignKeys == $connection->select("SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' ORDER BY TABLE_NAME, CONSTRAINT_NAME", [$schema]), 'Foreign keys changed');
    $migration->up();
    checkPk(Artisan::call('db:audit-primary-keys', ['--database'=>'pk_test']) === 0, 'Repaired schema still missing keys');
    checkPk(Artisan::call('db:audit-primary-keys', ['--database'=>'pk_test', '--all-schemas'=>true]) === 1, 'Cluster audit must include other schemas');
    checkPk(str_contains(Artisan::output(), 'untouched'), 'Other schema missing from audit');
    try { $migration->down(); throw new LogicException('Rollback unexpectedly succeeded'); }
    catch (RuntimeException $e) { checkPk(str_contains($e->getMessage(), 'must not be automatically removed'), 'Wrong rollback failure'); }

    // Preflight must detect conflicts before modifying the earlier tables.
    $connection->statement('ALTER TABLE click_geo DROP COLUMN `_replication_id`');
    $connection->statement('ALTER TABLE referrals MODIFY `_replication_id` BIGINT UNSIGNED NOT NULL, DROP PRIMARY KEY');
    try { $migration->up(); throw new LogicException('Conflict unexpectedly accepted'); }
    catch (RuntimeException $e) { checkPk(str_contains($e->getMessage(), 'Review referrals'), 'Wrong preflight failure'); }
    checkPk(!$connection->getSchemaBuilder()->hasColumn('click_geo', '_replication_id'), 'Preflight modified a table before detecting conflict');

    // Verify both clean-install templates independently, including missing-table skips.
    $connection->statement("USE `{$other}`");
    $connection->setDatabaseName($other);
    $migration->up();
    $connection->statement('DROP TABLE untouched');
    foreach (['base_install.sql', 'storage/base_install.sql'] as $file) {
        preg_match_all('/CREATE TABLE `[^`]+` \(.*?\) ENGINE[^;]+;/s', file_get_contents(__DIR__.'/../'.$file), $creates);
        foreach ($creates[0] as $sql) { $connection->unprepared($sql); }
        checkPk(Artisan::call('db:audit-primary-keys', ['--database'=>'pk_test']) === 0, "Template {$file} has missing primary keys");
        $migration->up();
        foreach ($creates[0] as $sql) {
            preg_match('/CREATE TABLE (`[^`]+`)/', $sql, $name);
            $connection->statement('DROP TABLE '.$name[1]);
        }
    }
    echo "Passed {$checks} primary-key checks on MySQL.\n";
} finally {
    $connection->statement("DROP DATABASE IF EXISTS `{$schema}`");
    $connection->statement("DROP DATABASE IF EXISTS `{$other}`");
    DB::disconnect('pk_test');
}
