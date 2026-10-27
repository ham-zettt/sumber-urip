<?php
/**
 * Sumber Urip - Database Connection (PDO Singleton)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Set MySQL session timezone to Asia/Jakarta (+07:00)
                self::$instance->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e) {
                error_log('Database Connection Error: ' . $e->getMessage());
                if (APP_DEBUG) {
                    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
                } else {
                    die('Terjadi kesalahan koneksi database. Silakan hubungi pengelola.');
                }
            }
        }

        return self::$instance;
    }
}
