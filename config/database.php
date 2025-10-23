<?php
class DB {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo === null) {
            $host = env('DB_HOST', 'localhost');
            $db   = env('DB_NAME', 'crafting_restaurante');
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');
            $charset = env('DB_CHARSET', 'utf8mb4');
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }
}