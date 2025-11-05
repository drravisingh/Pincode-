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

$pdo = create_database_connection();
$GLOBALS['app_pdo'] = $pdo;
initialize_settings_cache($pdo);

return $pdo;
