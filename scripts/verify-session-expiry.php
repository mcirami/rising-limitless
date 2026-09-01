<?php

/** CLI-only regression checks for expired legacy and Laravel sessions. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__.'/../vendor/autoload.php';

use App\Http\Controllers\LegacyLoginController;
use App\Http\Middleware\LegacyUserAuth;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use LeadMax\TrackYourStats\Database\DatabaseConnection;
use LeadMax\TrackYourStats\User\User;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.connections.master' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    'database.default' => 'master',
    'session.driver' => 'array',
]);

DB::purge('master');
$database = DB::connection('master');
$connection = new ReflectionProperty(DatabaseConnection::class, 'instanceMaster');
$connection->setValue(null, $database->getPdo());
DatabaseConnection::changeConnection($database->getPdo());
$database->statement('CREATE TABLE logins (session_id TEXT, repid INTEGER, success INTEGER, last_action_time INTEGER, ip TEXT)');

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$checks = 0;
function checkSessionExpiry($condition, $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

$_SESSION = [];
checkSessionExpiry((new User())->logout(), 'Logout failed with an empty expired session');

$_SESSION = ['salt' => 'partial-session'];
checkSessionExpiry(!(new User())->verify_login_session(false), 'A partial session was accepted');
checkSessionExpiry((new User())->logout(), 'Logout failed with a partial session');

$salt = 'expired-session';
$database->table('logins')->insert([
    'session_id' => hash('sha256', $salt),
    'repid' => 42,
    'success' => 1,
    'last_action_time' => time() - 86401,
    'ip' => $_SERVER['REMOTE_ADDR'],
]);
$_SESSION = ['salt' => $salt, 'repid' => 42, 'user_session' => 'expired-user'];
$protectedRequest = Request::create('/dashboard', 'GET');
$protectedResponse = (new LegacyUserAuth())->handle($protectedRequest, fn () => response('unexpected'));
checkSessionExpiry($protectedResponse->isRedirect() && str_ends_with($protectedResponse->getTargetUrl(), '/login'), 'Expired protected access did not redirect to login');
checkSessionExpiry($database->table('logins')->where('repid', 42)->value('success') === 1, 'Access check performed destructive logout cleanup');

checkSessionExpiry((new User())->logout(), 'Complete legacy logout failed');
checkSessionExpiry($database->table('logins')->where('repid', 42)->value('success') === 2, 'Legacy login record was not closed');
checkSessionExpiry(!isset($_SESSION['salt'], $_SESSION['repid'], $_SESSION['user_session']), 'Legacy authentication data survived logout');

$laravelSession = app('session')->driver();
$laravelSession->start();
app('redirect')->setSession($laravelSession);
$logoutRequest = Request::create('/logout', 'GET');
$logoutRequest->setLaravelSession($laravelSession);
$app->instance('request', $logoutRequest);
$_SESSION = [];
$logoutResponse = (new LegacyLoginController())->logout($logoutRequest);
checkSessionExpiry($logoutResponse->isRedirect() && str_ends_with($logoutResponse->getTargetUrl(), '/login'), 'Expired logout endpoint did not redirect to login');

$_SESSION = ['salt' => 'stale-token', 'repid' => 55, 'user_session' => 'stale-user'];
$expiredPost = Request::create('/announcements', 'POST');
$expiredPost->setLaravelSession($laravelSession);
$app->instance('request', $expiredPost);
$expiredResponse = app(ExceptionHandler::class)->render($expiredPost, new TokenMismatchException());
checkSessionExpiry($expiredResponse->isRedirect() && str_ends_with($expiredResponse->getTargetUrl(), '/login'), 'Expired CSRF submission did not redirect to login');
checkSessionExpiry(!isset($_SESSION['salt'], $_SESSION['repid'], $_SESSION['user_session']), 'Expired CSRF submission retained legacy authentication');

echo "Passed {$checks} expired-session assertions.\n";
