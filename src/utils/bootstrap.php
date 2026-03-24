<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../services/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize database schema
try {
    $db = \App\Database\Connection::getInstance();
    $migrator = new \App\Database\Migrator($db);
    $migrator->up();
    $migrator->seed();
} catch (\Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
}
