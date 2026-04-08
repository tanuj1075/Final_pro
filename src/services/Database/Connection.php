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
            $dbPath = self::resolveDatabasePath();

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
     * Resolve the SQLite database path, preferring APP_DATA_DIR (e.g. /tmp on Vercel).
     */
    private static function resolveDatabasePath(): string
    {
        $envDataDir = getenv('APP_DATA_DIR');
        $isVercel = (getenv('VERCEL') === '1') || (getenv('VERCEL_ENV') !== false);

        if (is_string($envDataDir) && trim($envDataDir) !== '') {
            $dbPath = rtrim(trim($envDataDir), '/') . '/app.sqlite';
        } elseif ($isVercel) {
            // Vercel filesystem is read-only except /tmp.
            $dbPath = '/tmp/ackerstream_data/app.sqlite';
        } else {
            $dbPath = __DIR__ . '/../../data/app.sqlite';
        }

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir) && !@mkdir($dbDir, 0775, true) && !is_dir($dbDir)) {
            throw new RuntimeException('Unable to create database directory: ' . $dbDir);
        }

        self::bootstrapDatabaseIfMissing($dbPath);

        return $dbPath;
    }

    /**
     * On ephemeral filesystems (e.g. Vercel /tmp), copy the seed DB on first run.
     */
    private static function bootstrapDatabaseIfMissing(string $dbPath): void
    {
        if (is_file($dbPath)) {
            return;
        }

        $seedDbPath = __DIR__ . '/../../data/app.sqlite';
        if (is_file($seedDbPath)) {
            if (!@copy($seedDbPath, $dbPath)) {
                throw new RuntimeException('Failed to copy seed database to: ' . $dbPath);
            }
            return;
        }

        // If no seed exists, allow SQLite to create a fresh DB file.
        if (@touch($dbPath) === false) {
            throw new RuntimeException('Failed to initialize database file: ' . $dbPath);
        }
    }
}
