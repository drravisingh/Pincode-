<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');

$configFile = __DIR__ . '/../config/config.php';
$fallbackConfigFile = __DIR__ . '/../public/install/runtime-config.php';

$configLoaded = false;
if (is_readable($configFile)) {
    require_once $configFile;
    $configLoaded = true;
} elseif (is_readable($fallbackConfigFile)) {
    require_once $fallbackConfigFile;
    $configLoaded = true;
}

if (!$configLoaded) {
    http_response_code(503);
    echo '<h1>Configuration missing</h1>';
    echo '<p>The application has not been configured yet. Run the <a href="/install.php">installation wizard</a> to continue.</p>';
    exit;
}

require_once __DIR__ . '/helpers/view.php';
require_once __DIR__ . '/helpers/database.php';
require_once __DIR__ . '/helpers/settings.php';

try {
    $pdo = create_database_connection();
} catch (Throwable $exception) {
    error_log('Database bootstrap failed: ' . $exception->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Service temporarily unavailable</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:60px;color:#1f2937;}';
    echo '.card{max-width:640px;margin:0 auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 12px 30px rgba(15,23,42,0.12);}';
    echo 'h1{margin-top:0;font-size:26px;color:#111827;}p{line-height:1.55;}a{color:#2563eb;text-decoration:none;}';
    echo '</style></head><body><div class="card">';
    echo '<h1>We\'re setting things up</h1>';
    echo '<p>The application cannot connect to the database right now. Please verify your database credentials in <code>config/config.php</code> and ensure the database server is reachable.</p>';
    echo '<p>If you recently installed the application, re-run the <a href="/install.php?force=1">installer</a> to double-check your settings.</p>';
    echo '</div></body></html>';
    exit;
}

$GLOBALS['app_pdo'] = $pdo;
initialize_settings_cache($pdo);

return $pdo;
