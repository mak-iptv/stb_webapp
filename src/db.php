<?php
namespace App;
use PDO;

class DB {
    private static $pdo = null;
    public static function get() {
        if (self::$pdo === null) {
            $dsn = getenv('DB_DSN');
            $user = getenv('DB_USER');
            $pass = getenv('DB_PASS');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }
}
