<?php
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config/config.php';
$schemaPath = dirname(__DIR__) . '/database/schema.sql';
$schemaFallbackPath = __DIR__ . '/install/schema.php';
$configFallbackPath = __DIR__ . '/install/runtime-config.php';

$currentConfig = is_readable($configPath) ? (string) file_get_contents($configPath) : '';
$fallbackConfig = is_readable($configFallbackPath) ? (string) file_get_contents($configFallbackPath) : '';
$hasPlaceholder = $currentConfig !== '' && strpos($currentConfig, 'change_me') !== false;
$isLocked = false;
$lockSource = '';

if (!isset($_GET['force'])) {
    if (trim($currentConfig) !== '' && !$hasPlaceholder) {
        $isLocked = true;
        $lockSource = 'config/config.php';
    } elseif (trim($fallbackConfig) !== '') {
        $isLocked = true;
        $lockSource = 'public/install/runtime-config.php';
    }
}

if ($isLocked) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Already installed</title><style>body{font-family:Arial,sans-serif;background:#f5f6ff;color:#333;padding:40px;}a{color:#4f46e5;text-decoration:none;}a.button{display:inline-block;padding:10px 18px;border-radius:6px;background:#4f46e5;color:#fff;margin-top:20px;}</style></head><body>';
    echo '<h1>Application already installed</h1>';
    $lockMessage = $lockSource !== ''
        ? sprintf(
            'The configuration is already set in <code>%s</code>.',
            htmlspecialchars($lockSource, ENT_QUOTES, 'UTF-8')
        )
        : 'The configuration file already contains custom values.';
    echo '<p>' . $lockMessage . ' If you need to re-run the installer, append <code>?force=1</code> to this URL.</p>';
    echo '<p><a class="button" href="/admin">Go to admin panel</a></p>';
    echo '</body></html>';
    return;
}

$errors = [];
$warnings = [];
$success = false;
$pdo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $dbPort = (int) ($_POST['db_port'] ?? 3306);
    $dbName = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $siteName = trim((string) ($_POST['site_name'] ?? 'India PIN Code Directory'));
    $siteUrl = rtrim(trim((string) ($_POST['site_url'] ?? 'http://localhost')), '/');
    $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');

    if ($dbName === '' || $dbUser === '') {
        $errors[] = 'Database name and user are required.';
    }
    if ($adminUser === '' || $adminPass === '') {
        $errors[] = 'Admin username and password are required.';
    }

    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            $errors[] = 'Database connection failed: ' . $exception->getMessage();
        }
    }

    if (empty($errors) && $pdo instanceof PDO) {
        $schema = false;
        if (is_readable($schemaPath)) {
            $schema = file_get_contents($schemaPath);
        }

        if ((!is_string($schema) || $schema === '') && is_readable($schemaFallbackPath)) {
            $schema = @include $schemaFallbackPath;

        }

        if (!is_string($schema) || $schema === '') {
            $errors[] = 'Unable to read the database schema file. Ensure database/schema.sql or public/install/schema.php is readable.';
        } else {
            $schema = preg_replace('/^\s*--.*$/m', '', $schema);
            $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);
            $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema)));

            try {
                $pdo->beginTransaction();
                foreach ($statements as $sql) {
                    if ($sql !== '') {
                        $pdo->exec($sql);
                    }
                }
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $errors[] = 'Failed to import schema: ' . $exception->getMessage();
            }
        }
    }

    if (empty($errors) && $pdo instanceof PDO) {
        $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO admin_users (username, email, password, role, is_active) VALUES (?, ?, ?, "admin", 1) ON DUPLICATE KEY UPDATE email = VALUES(email), password = VALUES(password), is_active = VALUES(is_active)')
            ->execute([$adminUser, $adminEmail !== '' ? $adminEmail : null, $passwordHash]);

        $settings = [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'seo_default_title' => $siteName !== '' ? $siteName . ' - Complete Postal Code Directory' : 'India PIN Code Directory - Complete Postal Code Directory',
            'seo_default_description' => 'Find accurate PIN codes for every Indian post office. Browse verified data, maps, and nearby services.',
            'seo_default_keywords' => 'pin code, postal code, india post office, pin code finder',
            'seo_additional_head_html' => '',
            'seo_structured_data' => '',
            'seo_focus_keywords' => '',
            'seo_backlink_targets' => '',
            'seo_content_calendar' => '',
            'seo_outreach_notes' => '',
            'search_console_meta_tag' => '',
            'analytics_measurement_id' => '',
            'analytics_additional_script' => '',
            'adsense_publisher_id' => '',
            'adsense_auto_ads_code' => '',
            'adsense_top_banner' => '',
            'adsense_home_featured' => '',
            'adsense_incontent_unit' => '',
            'adsense_sidebar_unit' => '',
            'adsense_footer_unit' => '',
            'adsense_strategy_notes' => '',
            'maps_api_key' => '',
            'maps_nearby_categories' => "Post Office\nATM\nBank\nHospital\nPolice Station",
        ];
        foreach ($settings as $key => $value) {
            $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP')
                ->execute([$key, $value]);
        }

        $configTemplate = <<<'PHP'
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(404);
    exit;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', '%s');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', %d);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', '%s');
}
if (!defined('DB_USER')) {
    define('DB_USER', '%s');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '%s');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', '%s');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', '%s');
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600);
}
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true);
}
if (!defined('CACHE_DURATION')) {
    define('CACHE_DURATION', 86400);
}
if (!defined('REDIS_ENABLED')) {
    define('REDIS_ENABLED', false);
}
if (!defined('REDIS_HOST')) {
    define('REDIS_HOST', '127.0.0.1');
}
if (!defined('REDIS_PORT')) {
    define('REDIS_PORT', 6379);
}
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Asia/Kolkata');
}

date_default_timezone_set(APP_TIMEZONE);
PHP;

        $configContents = sprintf(
            $configTemplate,
            addslashes($dbHost),
            $dbPort,
            addslashes($dbName),
            addslashes($dbUser),
            addslashes($dbPass),
            addslashes($siteUrl),
            addslashes($siteName)
        );

        $configWriteOk = @file_put_contents($configPath, $configContents) !== false;
        $fallbackWriteOk = @file_put_contents($configFallbackPath, $configContents) !== false;

        if ($configWriteOk) {
            @chmod($configPath, 0660);
        }

        if ($fallbackWriteOk) {
            @chmod($configFallbackPath, 0660);
        }

        if (!$configWriteOk && !$fallbackWriteOk && (!is_readable($configPath) || trim((string) @file_get_contents($configPath)) === '')) {
            $errors[] = 'Unable to write configuration file. Check filesystem permissions.';
        } else {
            if (!$configWriteOk && $fallbackWriteOk) {
                $warnings[] = 'The installer could not write to config/config.php. The application will use the runtime copy in public/install/runtime-config.php. Grant write access to config/config.php to make this permanent.';
            }

            $success = true;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pincode Directory Installer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f6ff; padding: 40px; color: #1f2937; }
        .container { max-width: 720px; margin: 0 auto; background: #fff; padding: 35px 40px; border-radius: 16px; box-shadow: 0 20px 60px rgba(15,23,42,0.12); }
        h1 { font-size: 28px; margin-bottom: 20px; color: #111827; }
        p { color: #4b5563; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px; }
        label { display: flex; flex-direction: column; font-weight: 600; color: #374151; font-size: 14px; gap: 6px; }
        input { padding: 12px 14px; border-radius: 10px; border: 1px solid #d1d5db; font-size: 15px; }
        input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .btn { display: inline-block; padding: 14px 28px; border-radius: 10px; border: none; background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; box-shadow: 0 12px 30px rgba(79,70,229,0.35); transition: transform .2s ease; }
        .btn:hover { transform: translateY(-2px); }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
        .footer-note { margin-top: 25px; font-size: 13px; color: #6b7280; }
        @media (max-width: 640px) {
            body { padding: 20px; }
            .container { padding: 25px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Pincode Directory Installer</h1>
        <p>Provide your database credentials and the installer will configure the application, create required tables, and set up the first admin account.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <strong>Installation failed:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Configuration saved and database initialised. You can now <a href="/admin">log in to the admin panel</a>.
            </div>
        <?php endif; ?>

        <?php if ($warnings): ?>
            <div class="alert" style="background:#fef3c7;color:#92400e;">
                <strong>Warning:</strong>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <h2 style="margin-top:10px;margin-bottom:16px;">Database connection</h2>
            <div class="grid">
                <label>Host
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                </label>
                <label>Port
                    <input type="number" name="db_port" value="<?php echo htmlspecialchars((string) ($_POST['db_port'] ?? '3306')); ?>" required>
                </label>
            </div>
            <div class="grid">
                <label>Database Name
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
                </label>
                <label>Database User
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                </label>
            </div>
            <div class="grid">
                <label>Database Password
                    <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                </label>
            </div>

            <h2 style="margin-top:26px;margin-bottom:16px;">Site information</h2>
            <div class="grid">
                <label>Site Name
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? 'India PIN Code Directory'); ?>" required>
                </label>
                <label>Site URL
                    <input type="text" name="site_url" value="<?php echo htmlspecialchars($_POST['site_url'] ?? 'http://localhost'); ?>" required>
                </label>
            </div>

            <h2 style="margin-top:26px;margin-bottom:16px;">Admin account</h2>
            <div class="grid">
                <label>Admin Username
                    <input type="text" name="admin_user" value="<?php echo htmlspecialchars($_POST['admin_user'] ?? 'admin'); ?>" required>
                </label>
                <label>Admin Email
                    <input type="email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>">
                </label>
            </div>
            <div class="grid">
                <label>Admin Password
                    <input type="password" name="admin_pass" value="" required>
                </label>
            </div>

            <button class="btn" type="submit">Install Application</button>
        </form>
        <?php endif; ?>

        <p class="footer-note">Need to rerun the installer later? Visit <code>/install.php?force=1</code>. Remember to remove this file once your site is live.</p>
    </div>
</body>
</html>
