<?php
use Dotenv\Dotenv;

// composer auto load
include __DIR__. "/../vendor/autoload.php";

// .env

$dotEnv = Dotenv::createImmutable(__DIR__.'/..');
$dotEnv->load();

// set default timezone
    date_default_timezone_set(env('TIMEZONE') ?: 'UTC');


    if (env('APP_DEBUG')) {
        set_error_handler("handle_error");
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

    }

    session_start();


//  TYS Install Connection
    $con = new \LeadMax\TrackYourStats\System\Connection();
    $con->setConnection();

//	unset($_SESSION["company"]);

// find company information
    // Refresh once per request so saved settings reach existing signed-in sessions.
    $company = new LeadMax\TrackYourStats\System\Company();
    $company->reloadSettings();

    // Refresh old serialized permission objects once when a deployment adds columns.
    if (isset($_SESSION['repid'], $_SESSION['permissions'])) {
        $cachedPermissions = @unserialize($_SESSION['permissions'], ['allowed_classes' => true]);
        $knownPermissions = array_diff(array_keys(LeadMax\TrackYourStats\User\Permissions::$permissionsArray), ['aff_id']);
        $cachedKeys = is_object($cachedPermissions) && is_array($cachedPermissions->permissions ?? null)
            ? array_keys($cachedPermissions->permissions)
            : [];
        if (array_diff($knownPermissions, $cachedKeys)) {
            $_SESSION['permissions'] = serialize(new LeadMax\TrackYourStats\User\Permissions((int) $_SESSION['repid']));
        }
    }
