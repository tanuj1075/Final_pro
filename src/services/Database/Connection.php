<?php

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Handles the application's PDO database connection using the Singleton pattern.
 */
class Connection
{
    private static ?PDO $instance = null;

    /**
     * Get the singleton PDO database connection.
     *
     * @return PDO
     * @throws RuntimeException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dataDir = self::resolveDataDirectory();
            $dbPath = rtrim($dataDir, '/') . '/app.sqlite';

            try {
                self::$instance = new PDO('sqlite:' . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage() . ' (path: ' . $dbPath . ')');
            }
        }

        return self::$instance;
    }

    /**
     * Locate a writable data directory for the SQLite database.
     *
     * @return string
     * @throws RuntimeException
     */
    private static function resolveDataDirectory(): string
    {
        $candidates = [];

        $envDataDir = getenv('APP_DATA_DIR');
        if (is_string($envDataDir) && trim($envDataDir) !== '') {
            $candidates[] = rtrim(trim($envDataDir), '/');
        }

        $candidates[] = dirname(__DIR__, 2) . '/data';

        $tmpRoot = rtrim(sys_get_temp_dir(), '/');
        if ($tmpRoot !== '') {
            $candidates[] = $tmpRoot . '/final_pro_data';
        }

        $homeDir = getenv('HOME');
        if (is_string($homeDir) && trim($homeDir) !== '') {
            $candidates[] = rtrim(trim($homeDir), '/') . '/.final_pro/data';
        }

        foreach ($candidates as $candidate) {
            if (self::ensureWritableDirectory($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to create or write to any data directory candidate');
    }

    /**
     * Ensure a directory exists and is writable.
     *
     * @param string $path
     * @return bool
     */
    private static function ensureWritableDirectory(string $path): bool
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            return false;
        }

        return is_writable($path);
    }
}
