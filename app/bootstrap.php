<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');

$projectRoot = dirname(__DIR__);
$logCandidates = [
    $projectRoot . '/storage/logs/application.log',
    sys_get_temp_dir() . '/pincode-application.log',
];

$selectedLogFile = null;
foreach ($logCandidates as $candidate) {
    $directory = dirname($candidate);
    if (!is_dir($directory) && !@mkdir($directory, 0775, true)) {
        continue;
    }

    if (!is_writable($directory)) {
        continue;
    }

    if (!file_exists($candidate) && @touch($candidate) === false) {
        continue;
    }

    if (!is_writable($candidate)) {
        continue;
    }

    $selectedLogFile = $candidate;
    break;
}

ini_set('log_errors', '1');
if ($selectedLogFile !== null) {
    ini_set('error_log', $selectedLogFile);
    if (!defined('APP_LOG_FILE')) {
        define('APP_LOG_FILE', $selectedLogFile);
    }
} elseif (!defined('APP_LOG_FILE')) {
    $existingLog = ini_get('error_log');
    if (is_string($existingLog) && $existingLog !== '') {
        define('APP_LOG_FILE', $existingLog);
    }
}

require_once __DIR__ . '/helpers/logger.php';

$requestContext = static function (): array {
    return [
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'referer' => $_SERVER['HTTP_REFERER'] ?? null,
    ];
};

if (!function_exists('render_error_card')) {
    function render_error_card(string $heading, array $paragraphs, int $statusCode = 500): void
    {
        if (headers_sent() === false) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:60px;color:#1f2937;}';
        echo '.card{max-width:640px;margin:0 auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 12px 30px rgba(15,23,42,0.12);}';
        echo 'h1{margin-top:0;font-size:26px;color:#111827;}p{line-height:1.55;}a{color:#2563eb;text-decoration:none;}';
        echo '</style></head><body><div class="card">';
        echo '<h1>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h1>';
        foreach ($paragraphs as $paragraph) {
            echo '<p>' . $paragraph . '</p>';
        }
        echo '</div></body></html>';
    }
}

$logHelpText = defined('APP_LOG_FILE')
    ? 'Review the detailed error log at <code>' . htmlspecialchars(APP_LOG_FILE, ENT_QUOTES, 'UTF-8') . '</code> for more information.'
    : 'Review the server error logs for more information.';

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
    app_log('Configuration not loaded', [
        'config_file' => $configFile,
        'fallback_file' => $fallbackConfigFile,
        'request' => $requestContext(),
    ]);

    render_error_card('Configuration missing', [
        'The application has not been configured yet. Run the <a href="/install.php">installation wizard</a> to continue.',
        $logHelpText,
    ], 503);
    exit;
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

$errorTypeMap = [
    E_ERROR => 'E_ERROR',
    E_WARNING => 'E_WARNING',
    E_PARSE => 'E_PARSE',
    E_NOTICE => 'E_NOTICE',
    E_CORE_ERROR => 'E_CORE_ERROR',
    E_CORE_WARNING => 'E_CORE_WARNING',
    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
    E_USER_ERROR => 'E_USER_ERROR',
    E_USER_WARNING => 'E_USER_WARNING',
    E_USER_NOTICE => 'E_USER_NOTICE',
    E_STRICT => 'E_STRICT',
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    E_DEPRECATED => 'E_DEPRECATED',
    E_USER_DEPRECATED => 'E_USER_DEPRECATED',
];

set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($requestContext, $errorTypeMap): bool {
    $type = $errorTypeMap[$severity] ?? 'E_UNKNOWN';
    $context = [
        'type' => $type,
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'request' => $requestContext(),
    ];

    if (!(error_reporting() & $severity)) {
        app_log('Silenced PHP error', $context);
        return false;
    }

    app_log('PHP runtime error', $context);
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception) use ($requestContext, $logHelpText): void {
    app_log_exception($exception, [
        'request' => $requestContext(),
    ]);

    if (APP_DEBUG) {
        if (headers_sent() === false) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo '<h1>Application error</h1>';
        echo '<pre style="white-space:pre-wrap;">' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>';
        return;
    }

    $pdoException = null;
    if ($exception instanceof PDOException) {
        $pdoException = $exception;
    } elseif ($exception->getPrevious() instanceof PDOException) {
        $pdoException = $exception->getPrevious();
    }

    $paragraphs = [];

    if ($pdoException instanceof PDOException) {
        $sqlState = $pdoException->errorInfo[0] ?? $pdoException->getCode();
        if ((string) $sqlState === '42S02') {
            $paragraphs[] = 'The database tables are missing. Please run the installer again or import the schema from <code>database/schema.sql</code>.';
            $paragraphs[] = 'If you completed the installation already, double-check that the configured database contains the <code>pincode_master</code> table.';
        } else {
            $paragraphs[] = 'The database responded with an error that we could not automatically resolve. Verify your credentials in <code>config/config.php</code> and make sure the database server is reachable.';
        }
    } else {
        $paragraphs[] = 'Something went wrong while rendering this page. The error has been logged for review.';
    }

    $paragraphs[] = $logHelpText;
    $paragraphs[] = '<a href="/admin">Try the admin panel</a> or <a href="/install.php?force=1">rerun the installer</a> once the issue is fixed.';

    render_error_card('We hit a snag', $paragraphs, 500);
});

register_shutdown_function(static function () use ($requestContext, $logHelpText, $errorTypeMap): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    app_log('Fatal runtime error', [
        'type' => $errorTypeMap[$error['type']] ?? 'E_UNKNOWN',
        'message' => $error['message'],
        'file' => $error['file'],
        'line' => $error['line'],
        'request' => $requestContext(),
    ]);

    if (APP_DEBUG) {
        return;
    }

    render_error_card('We hit a snag', [
        'The application stopped unexpectedly while processing your request.',
        $logHelpText,
    ], 500);
});

require_once __DIR__ . '/helpers/view.php';
require_once __DIR__ . '/helpers/database.php';
require_once __DIR__ . '/helpers/settings.php';

try {
    $pdo = create_database_connection();
} catch (Throwable $exception) {
    app_log_exception($exception, [
        'stage' => 'database_bootstrap',
        'request' => $requestContext(),
    ]);

    render_error_card('We\'re setting things up', [
        'The application cannot connect to the database right now. Please verify your database credentials in <code>config/config.php</code> and ensure the database server is reachable.',
        'If you recently installed the application, re-run the <a href="/install.php?force=1">installer</a> to double-check your settings.',
        $logHelpText,
    ], 503);
    exit;
}

$GLOBALS['app_pdo'] = $pdo;
initialize_settings_cache($pdo);

return $pdo;
