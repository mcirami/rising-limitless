<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('The legacy primary-key repair requires MySQL.');
        }

        $tables = [
            'click_geo', 'click_has_bonus', 'click_vars', 'offer_caps',
            'referrals', 'user_has_bonus', 'user_has_notification',
        ];
        $pending = [];
        foreach ($tables as $table) {
            $table = $connection->getTablePrefix().$table;
            $exists = $connection->selectOne(
                "SELECT TABLE_NAME FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND TABLE_TYPE = 'BASE TABLE'",
                [$table]
            );
            if (!$exists) {
                continue;
            }
            $primary = $connection->selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'PRIMARY KEY'",
                [$table]
            );
            // Also allows safe retries after a partially completed MySQL DDL migration.
            if ($primary) {
                continue;
            }
            $conflict = $connection->selectOne(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                 AND (COLUMN_NAME = '_replication_id' OR EXTRA LIKE '%auto_increment%')",
                [$table]
            );
            if ($conflict) {
                throw new RuntimeException("Review {$table}: an existing replication ID or AUTO_INCREMENT column prevents automatic repair.");
            }
            $pending[] = $table;
        }

        // Do not infer uniqueness from business columns or discard duplicate records.
        // The distinct name avoids ambiguous `id` references in legacy joins.
        $lockTimeout = $connection->selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')->seconds;
        try {
            $connection->statement('SET SESSION lock_wait_timeout = 15');
            foreach ($pending as $table) {
                $quoted = '`'.str_replace('`', '``', $table).'`';
                $connection->statement("ALTER TABLE {$quoted} ADD COLUMN `_replication_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
            }
        } finally {
            $connection->statement('SET SESSION lock_wait_timeout = '.(int) $lockTimeout);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Primary keys protect replication and must not be automatically removed. Use a reviewed forward migration.');
    }
};
