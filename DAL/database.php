<?php
/**
 * Database - Singleton PDO connection
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . AppConfig::DB_HOST
                    . ';port=' . AppConfig::DB_PORT
                    . ';dbname=' . AppConfig::DB_NAME
                    . ';charset=' . AppConfig::DB_CHARSET;
                self::$instance = new PDO($dsn, AppConfig::DB_USER, AppConfig::DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                if (AppConfig::APP_DEBUG) {
                    die('DB connection error: ' . $e->getMessage());
                }
                die('Lỗi kết nối cơ sở dữ liệu.');
            }
        }
        return self::$instance;
    }

    public static function beginTransaction(): void
    {
        self::getConnection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getConnection()->commit();
    }

    public static function rollBack(): void
    {
        if (self::getConnection()->inTransaction()) {
            self::getConnection()->rollBack();
        }
    }

    /**
     * Hydrate: gán kết quả query vào object
     */
    public static function hydrate(array $row, string $class)
    {
        $obj = new $class();
        foreach ($row as $k => $v) {
            if (property_exists($obj, $k)) {
                $obj->$k = $v;
            }
        }
        return $obj;
    }
}