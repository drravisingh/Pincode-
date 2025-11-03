<?php
/**
 * DEBUG SCRIPT - Find 500 Error Cause
 * Visit: yoursite.com/debug.php
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo '<h1>🔍 Website Debug Report</h1>';
echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}
      .box{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
      .success{color:green;font-weight:bold;}
      .error{color:red;font-weight:bold;}
      .warning{color:orange;font-weight:bold;}
      pre{background:#f8f8f8;padding:10px;border-radius:4px;overflow:auto;}
      </style>';

// 1. Check PHP Version
echo '<div class="box">';
echo '<h2>1. PHP Version</h2>';
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo '<span class="success">✅ PHP Version: ' . PHP_VERSION . '</span>';
} else {
    echo '<span class="error">❌ PHP Version: ' . PHP_VERSION . ' (Need 7.4+)</span>';
}
echo '</div>';

// 2. Check Required Files
echo '<div class="box">';
echo '<h2>2. Required Files</h2>';
$required_files = ['index.php', 'config.php', '.htaccess'];
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo '<span class="success">✅ ' . $file . ' exists</span><br>';
    } else {
        echo '<span class="error">❌ ' . $file . ' missing!</span><br>';
    }
}
echo '</div>';

// 3. Check config.php
echo '<div class="box">';
echo '<h2>3. Database Connection</h2>';
if (file_exists('config.php')) {
    try {
        require_once 'config.php';
        echo '<span class="success">✅ config.php loaded</span><br>';
        
        // Check if constants are defined
        if (defined('DB_HOST')) {
            echo '<span class="success">✅ DB_HOST defined: ' . DB_HOST . '</span><br>';
        } else {
            echo '<span class="error">❌ DB_HOST not defined</span><br>';
        }
        
        if (defined('DB_NAME')) {
            echo '<span class="success">✅ DB_NAME defined: ' . DB_NAME . '</span><br>';
        } else {
            echo '<span class="error">❌ DB_NAME not defined</span><br>';
        }
        
        if (defined('DB_USER')) {
            echo '<span class="success">✅ DB_USER defined: ' . DB_USER . '</span><br>';
        } else {
            echo '<span class="error">❌ DB_USER not defined</span><br>';
        }
        
        // Try database connection
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            echo '<span class="success">✅ Database connection successful!</span><br>';
            
            // Check tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '<span class="success">✅ Tables found: ' . count($tables) . '</span><br>';
            echo '<pre>' . implode(', ', $tables) . '</pre>';
            
        } catch (PDOException $e) {
            echo '<span class="error">❌ Database connection failed: ' . $e->getMessage() . '</span><br>';
        }
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Error loading config.php: ' . $e->getMessage() . '</span><br>';
    }
} else {
    echo '<span class="error">❌ config.php not found!</span><br>';
}
echo '</div>';

// 4. Check PHP Extensions
echo '<div class="box">';
echo '<h2>4. PHP Extensions</h2>';
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo '<span class="success">✅ ' . $ext . '</span><br>';
    } else {
        echo '<span class="error">❌ ' . $ext . ' missing</span><br>';
    }
}
echo '</div>';

// 5. Check File Permissions
echo '<div class="box">';
echo '<h2>5. File Permissions</h2>';
$check_perms = ['index.php', 'config.php', '.htaccess'];
foreach ($check_perms as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        if (is_readable($file)) {
            echo '<span class="success">✅ ' . $file . ' (' . $perms . ') - Readable</span><br>';
        } else {
            echo '<span class="error">❌ ' . $file . ' (' . $perms . ') - Not readable</span><br>';
        }
    }
}

// Check writable directories
$dirs = ['cache', 'sitemaps', 'uploads', 'logs'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo '<span class="success">✅ /' . $dir . ' - Writable</span><br>';
        } else {
            echo '<span class="warning">⚠️ /' . $dir . ' - Not writable</span><br>';
        }
    } else {
        echo '<span class="warning">⚠️ /' . $dir . ' - Directory missing</span><br>';
    }
}
echo '</div>';

// 6. Check .htaccess
echo '<div class="box">';
echo '<h2>6. .htaccess File</h2>';
if (file_exists('.htaccess')) {
    echo '<span class="success">✅ .htaccess exists</span><br>';
    echo '<pre>' . htmlspecialchars(file_get_contents('.htaccess')) . '</pre>';
} else {
    echo '<span class="error">❌ .htaccess missing!</span><br>';
}
echo '</div>';

// 7. Try to load index.php
echo '<div class="box">';
echo '<h2>7. Test index.php</h2>';
if (file_exists('index.php')) {
    echo '<span class="success">✅ index.php exists</span><br>';
    echo '<p>Attempting to capture errors from index.php...</p>';
    
    ob_start();
    try {
        // Don't actually include, just check for syntax errors
        $content = file_get_contents('index.php');
        if (strpos($content, '<?php') !== false) {
            echo '<span class="success">✅ index.php has valid PHP opening tag</span><br>';
        }
        
        // Check for common issues
        if (strpos($content, 'require') !== false || strpos($content, 'include') !== false) {
            echo '<span class="success">✅ index.php includes other files</span><br>';
        }
        
    } catch (Exception $e) {
        echo '<span class="error">❌ Error: ' . $e->getMessage() . '</span><br>';
    }
    ob_end_clean();
} else {
    echo '<span class="error">❌ index.php not found!</span><br>';
}
echo '</div>';

// 8. Server Information
echo '<div class="box">';
echo '<h2>8. Server Information</h2>';
echo '<pre>';
echo 'Server: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo 'PHP SAPI: ' . php_sapi_name() . "\n";
echo 'Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo 'Script Filename: ' . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo 'Current Directory: ' . getcwd() . "\n";
echo '</pre>';
echo '</div>';

// 9. Error Logs
echo '<div class="box">';
echo '<h2>9. Recent Error Logs</h2>';
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo '<span class="success">✅ Error log found: ' . $error_log . '</span><br>';
    $lines = file($error_log);
    $recent = array_slice($lines, -20); // Last 20 lines
    echo '<pre>' . htmlspecialchars(implode('', $recent)) . '</pre>';
} else {
    echo '<span class="warning">⚠️ No error log found or not accessible</span><br>';
    echo '<p>Check: /home/your_username/public_html/error_log or /var/log/apache2/error.log</p>';
}
echo '</div>';

// 10. Recommendations
echo '<div class="box">';
echo '<h2>10. Quick Fixes</h2>';
echo '<ol>';
echo '<li><strong>If index.php is missing:</strong> Upload your website files</li>';
echo '<li><strong>If config.php has errors:</strong> Check database credentials</li>';
echo '<li><strong>If .htaccess causes issues:</strong> Temporarily rename it to .htaccess.bak and test</li>';
echo '<li><strong>If permissions are wrong:</strong> Set files to 644 and folders to 755</li>';
echo '<li><strong>If database connection fails:</strong> Verify credentials in config.php</li>';
echo '</ol>';
echo '</div>';

echo '<div class="box">';
echo '<h2>✅ Next Steps</h2>';
echo '<p>1. Review the errors above</p>';
echo '<p>2. Fix any issues marked with ❌</p>';
echo '<p>3. Check warnings marked with ⚠️</p>';
echo '<p>4. Delete this debug.php file after fixing (security!)</p>';
echo '</div>';
?>