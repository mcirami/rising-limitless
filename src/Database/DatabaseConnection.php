<?php

namespace LeadMax\TrackYourStats\Database;

use PDO;

class DatabaseConnection
{
    private static $instance = null;

    private static $instanceMaster = null;

    public static function getOptions()
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (env('DB_SSL', false)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = base_path('resources/certs/digitalocean-ca.crt');
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        return $options;
    }

    public static function changeConnection($db)
    {
        self::$instance = $db;
    }


    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new PDO(
                "mysql:host=".env('DB_HOST').";port=".env('DB_PORT').";dbname=".env('DB_DATABASE').";charset=utf8mb4",
                env('DB_USERNAME'),
                env('DB_PASSWORD'),
                self::getOptions()
            );
        }

        return self::$instance;
    }


    public static function getMasterInstance()
    {
        if (!self::$instanceMaster) {
            self::$instanceMaster = new PDO(
                "mysql:host=".(env('MASTER_DB_HOST') ?: env('DB_HOST')).";port=".(env('MASTER_DB_PORT') ?: env('DB_PORT')).";dbname=".(env('MASTER_DB_DATABASE') ?: env('DB_DATABASE')).";charset=utf8mb4",
                env('MASTER_DB_USERNAME') ?: env('DB_USERNAME'),
                env('MASTER_DB_PASSWORD') ?: env('DB_PASSWORD'),
                self::getOptions()
            );
        }

        return self::$instanceMaster;
    }
}
