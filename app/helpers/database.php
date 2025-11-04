<?php
declare(strict_types=1);

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    throw new RuntimeException('Database constants are not defined. Ensure config/config.php is loaded.');
}

/**
 * Create (and memoize) the PDO connection for the application.
 */
function create_database_connection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
    $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, $port, DB_NAME, $charset);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException('Database connection failed. Please contact administrator.', 0, $exception);
    }

    return $pdo;
}
