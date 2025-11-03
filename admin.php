<?php
/**
 * Admin Panel - index.php
 * Full integrated version with built-in tools:
 * - CSV Importer
 * - Homepage Search / Popular
 * - Post Generator
 * - Router / Sitemap
 * - Pincode Template Editor
 *
 * Requirements: require_once 'config.php' providing DB constants:
 * DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_NAME (optional)
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration
require_once __DIR__ . '/config.php';

// DB Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed.');
}

// CSRF helper
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
function check_csrf($token) {
    return isset($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ------------------ Authentication ------------------

// Handle logout (POST preferred, GET legacy)
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']) && check_csrf($_POST['csrf_token'])) || (isset($_GET['logout']) && $_GET['logout'] == 1)) {
    session_unset();
    session_destroy();
    header('Location: /admin');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
        header('Location: /admin');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

$is_logged_in = isset($_SESSION['admin_id']);

// ------------------ Tool Functions (embedded) ------------------

/*
 * 1) CSV Importer - render and process
 * Expected columns (header): pincode,state,district,office_name,delivery_status,region,division,office_type,contact,remarks,views_count
 */
function render_csv_import_form_and_process($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
        if (!check_csrf($_POST['csrf_token'] ?? '')) {
            echo '<div class="error">Invalid CSRF token.</div>';
            return;
        }
        try {
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload failed.');
            }
            $maxBytes = 8 * 1024 * 1024; // 8 MB
            if ($_FILES['csv_file']['size'] > $maxBytes) throw new Exception('File too large (max 8MB).');

            $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') throw new Exception('Please upload a CSV file.');

            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($handle === false) throw new Exception('Cannot open uploaded file.');

            $header = fgetcsv($handle);
            if ($header === false) throw new Exception('CSV is empty.');

            $map = array_map(function($h){ return strtolower(trim($h)); }, $header);
            if (!in_array('pincode', $map)) throw new Exception('CSV must include "pincode" column.');

            $columns = ['pincode','state','district','office_name','delivery_status','region','division','office_type','contact','remarks','views_count'];
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $insertSql = "INSERT INTO pincode_master (" . implode(',', $columns) . ") VALUES ({$placeholders})
                          ON DUPLICATE KEY UPDATE state=VALUES(state), district=VALUES(district), office_name=VALUES(office_name), delivery_status=VALUES(delivery_status), region=VALUES(region), division=VALUES(division), office_type=VALUES(office_type), contact=VALUES(contact), remarks=VALUES(remarks), views_count=VALUES(views_count)";

            $pdo->beginTransaction();
            $stmt = $pdo->prepare($insertSql);

            $rowCount = 0;
            $batch = 0;
            $batchSize = 400;

            while (($row = fgetcsv($handle)) !== false) {
                $assoc = [];
                foreach ($map as $i => $colName) $assoc[$colName] = $row[$i] ?? null;
                $pincode = preg_replace('/\D+/', '', ($assoc['pincode'] ?? ''));
                if (strlen($pincode) < 3) continue;

                $values = [];
                foreach ($columns as $c) {
                    $val = $assoc[$c] ?? null;
                    if ($c === 'views_count') $val = is_numeric($val) ? (int)$val : 0;
                    $values[] = $val;
                }

                $stmt->execute($values);
                $rowCount++; $batch++;
                if ($batch >= $batchSize) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                    $batch = 0;
                }
            }
            $pdo->commit();
            fclose($handle);
            echo '<div style="background:#e6ffed;padding:12px;border-radius:8px;margin-bottom:10px;">Import completed: <strong>' . intval($rowCount) . '</strong> rows processed.</div>';
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo '<div class="error">Import error: ' . htmlspecialchars($ex->getMessage()) . '</div>';
        }
    }

    // Render form
    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;margin-bottom:16px;">
        <h3>Import PIN Codes (CSV)</h3>
        <p style="color:#666;font-size:13px">Expected columns: pincode,state,district,office_name,delivery_status,region,division,office_type,contact,remarks,views_count</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <br><br>
            <button type="submit" name="import_csv" class="action-btn">Upload & Import</button>
        </form>
    </div>
    <?php
}

/*
 * 2) Homepage Template tool: render search and popular list (admin view)
 */
function render_homepage_search_and_popular($pdo) {
    // Search form
    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;margin-bottom:16px;">
        <h3>Search PIN Codes</h3>
        <form method="GET" action="/search.php" target="_blank">
            <input type="text" name="q" placeholder="Enter city, pincode or area" style="padding:10px;border-radius:6px;border:1px solid #ddd;width:65%;">
            <button class="action-btn" type="submit">Search on Site</button>
        </form>
    </div>
    <?php
    // Popular list
    $stmt = $pdo->prepare("SELECT pincode, office_name, district, statename, views_count FROM pincode_master ORDER BY views_count DESC LIMIT 12");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;">
        <h3>Top Popular PIN Codes</h3>
        <?php if (!$rows): ?>
            <p style="color:#666">No data available.</p>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                <?php foreach ($rows as $r): ?>
                    <div style="padding:10px;border-radius:6px;border:1px solid #f0f0f0;">
                        <div style="font-weight:700;"><?php echo htmlspecialchars($r['pincode']); ?></div>
                        <div style="font-size:13px;color:#555;"><?php echo htmlspecialchars($r['office_name'] ?: $r['district'] . ', ' . $r['statename']); ?></div>
                        <div style="font-size:12px;color:#999;margin-top:6px;">Views: <?php echo intval($r['views_count']); ?></div>
                        <div style="margin-top:8px;"><a class="action-btn" href="/pincode/<?php echo urlencode($r['pincode']); ?>" target="_blank">Open Page</a></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/*
 * 3) Post Generator - create posts/pages from templates and pincode data
 * Expects content_templates table with columns: id, slug, title_template, body_template, created_at, updated_at
 * Templates may use placeholders: {{pincode}}, {{office_name}}, {{district}}, {{statename}}
 */
function render_post_generator($pdo) {
    // handle generate action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_post'])) {
        if (!check_csrf($_POST['csrf_token'] ?? '')) {
            echo '<div class="error">CSRF token invalid.</div>';
        } else {
            $template_id = (int)($_POST['template_id'] ?? 0);
            $limit = min(200, max(1, (int)($_POST['limit'] ?? 50)));
            // fetch chosen template
            $stmt = $pdo->prepare("SELECT * FROM content_templates WHERE id = ? LIMIT 1");
            $stmt->execute([$template_id]);
            $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$tpl) {
                echo '<div class="error">Template not found.</div>';
            } else {
                // fetch pincode rows
                $rowsStmt = $pdo->prepare("SELECT pincode, office_name, district, statename FROM pincode_master ORDER BY views_count DESC LIMIT ?");
                $rowsStmt->bindValue(1, $limit, PDO::PARAM_INT);
                $rowsStmt->execute();
                $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                $created = 0;
                $errors = [];
                foreach ($rows as $r) {
                    $search = ['{{pincode}}','{{office_name}}','{{district}}','{{statename}}'];
                    $replace = [htmlspecialchars($r['pincode']), htmlspecialchars($r['office_name']), htmlspecialchars($r['district']), htmlspecialchars($r['statename'])];
                    $title = str_replace($search, $replace, $tpl['title_template']);
                    $body = str_replace($search, $replace, $tpl['body_template']);

                    // Here you can choose to insert generated content into a posts table or save as static HTML file.
                    // Simple approach: insert to table `generated_posts` (create if not exist)
                    $pdo->exec("CREATE TABLE IF NOT EXISTS generated_posts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        slug VARCHAR(255) UNIQUE,
                        title VARCHAR(255),
                        body TEXT,
                        pincode VARCHAR(32),
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // create a slug
                    $slugBase = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower(substr($title,0,60)));
                    $slugBase = trim($slugBase, '-');
                    $slug = $slugBase . '-' . $r['pincode'];

                    // insert if not exists
                    $ins = $pdo->prepare("INSERT IGNORE INTO generated_posts (slug, title, body, pincode) VALUES (?, ?, ?, ?)");
                    $ok = $ins->execute([$slug, $title, $body, $r['pincode']]);
                    if ($ok && $ins->rowCount()) $created++;
                }

                echo '<div style="background:#e6ffed;padding:12px;border-radius:8px;margin-bottom:10px;">Generated posts: <strong>' . intval($created) . '</strong></div>';
                if ($errors) {
                    echo '<div class="error">Some errors: ' . implode(', ', $errors) . '</div>';
                }
            }
        }
    }

    // show template selection form
    $tpls = $pdo->query("SELECT id, slug, title_template FROM content_templates ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;margin-bottom:16px;">
        <h3>Post Generator</h3>
        <form method="POST">
            <label style="font-weight:600">Choose Template</label><br>
            <select name="template_id" required style="padding:8px;border-radius:6px;border:1px solid #ddd;width:60%;">
                <?php foreach ($tpls as $t): ?>
                    <option value="<?php echo intval($t['id']); ?>"><?php echo htmlspecialchars($t['slug'] . ' — ' . substr($t['title_template'],0,80)); ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>
            <label>How many top pincodes to use (limit)</label><br>
            <input type="number" name="limit" min="1" max="200" value="50" style="padding:8px;border-radius:6px;border:1px solid #ddd;width:120px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <br><br>
            <button type="submit" name="generate_post" class="action-btn">Generate Posts</button>
        </form>
        <p style="color:#666;margin-top:8px;">Generated posts are saved into table <code>generated_posts</code>. You can modify to save as files or into your CMS posts table.</p>
    </div>
    <?php
}

/*
 * 4) Router / Sitemap tool - preview and generate sitemap.xml
 */
function render_sitemap_tool($pdo) {
    // generate preview or write file
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_sitemap'])) {
        if (!check_csrf($_POST['csrf_token'] ?? '')) {
            echo '<div class="error">CSRF token invalid.</div>';
        } else {
            $limit = min(50000, max(100, (int)($_POST['limit'] ?? 5000)));
            // get base URL from settings or default
            $site_url = rtrim((string)($GLOBALS['SITE_URL'] ?? ''), '/');
            if (empty($site_url)) {
                // try settings table
                $s = $pdo->prepare("SELECT value FROM settings WHERE name='site_url' LIMIT 1");
                $s->execute();
                $site_url = rtrim($s->fetchColumn() ?: '', '/');
                if (empty($site_url)) $site_url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . $_SERVER['HTTP_HOST'];
            }

            // fetch pincode pages
            $stmt = $pdo->prepare("SELECT pincode, updated_at FROM pincode_master ORDER BY views_count DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // build xml
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($rows as $r) {
                $loc = htmlspecialchars($site_url . '/pincode/' . urlencode($r['pincode']));
                $lastmod = !empty($r['updated_at']) ? date('Y-m-d', strtotime($r['updated_at'])) : date('Y-m-d');
                $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>0.6</priority>\n  </url>\n";
            }
            $xml .= '</urlset>';

            // write to public sitemap.xml (attempt)
            $path = __DIR__ . '/../sitemap.xml'; // one level up (site root) - adjust as needed
            try {
                file_put_contents($path, $xml);
                echo '<div style="background:#e6ffed;padding:12px;border-radius:8px;margin-bottom:10px;">Sitemap generated at: <strong>' . htmlspecialchars($path) . '</strong></div>';
            } catch (Exception $ex) {
                echo '<div class="error">Could not write sitemap file. Error: ' . htmlspecialchars($ex->getMessage()) . '</div>';
            }
        }
    }

    // preview form
    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;margin-bottom:16px;">
        <h3>Sitemap / Router Generator</h3>
        <form method="POST">
            <label>Max URLs to include (max 50000)</label><br>
            <input type="number" name="limit" value="5000" min="100" max="50000" style="padding:8px;border-radius:6px;border:1px solid #ddd;width:140px;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <br><br>
            <button type="submit" name="generate_sitemap" class="action-btn">Generate sitemap.xml</button>
        </form>
        <p style="color:#666;margin-top:8px;">Sitemap will be written to project root <code>/sitemap.xml</code> (adjust path in code if needed).</p>
    </div>
    <?php
}

/*
 * 5) Pincode Template Editor - edit content template for pincode pages
 * Saves into content_templates table with slug 'pincode_page' or settings table
 */
function render_pincode_template_editor($pdo) {
    // ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) UNIQUE,
        title_template TEXT,
        body_template TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // handle save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pincode_template'])) {
        if (!check_csrf($_POST['csrf_token'] ?? '')) {
            echo '<div class="error">CSRF token invalid.</div>';
        } else {
            $title_tpl = $_POST['title_template'] ?? '';
            $body_tpl = $_POST['body_template'] ?? '';
            $slug = 'pincode_page';

            // upsert
            $stmt = $pdo->prepare("INSERT INTO content_templates (slug, title_template, body_template) VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE title_template = VALUES(title_template), body_template = VALUES(body_template), updated_at = NOW()");
            $stmt->execute([$slug, $title_tpl, $body_tpl]);
            echo '<div style="background:#e6ffed;padding:12px;border-radius:8px;margin-bottom:10px;">Template saved successfully.</div>';
        }
    }

    // load existing
    $stmt = $pdo->prepare("SELECT title_template, body_template FROM content_templates WHERE slug = 'pincode_page' LIMIT 1");
    $stmt->execute();
    $tpl = $stmt->fetch(PDO::FETCH_ASSOC);
    $title_tpl = $tpl['title_template'] ?? 'PIN Code {{pincode}} — {{office_name}}, {{district}}';
    $body_tpl = $tpl['body_template'] ?? '<h1>PIN {{pincode}} — {{office_name}}</h1><p>Location: {{district}}, {{statename}}</p><p>Details: ...</p>';

    ?>
    <div style="background:#fff;padding:18px;border-radius:8px;margin-bottom:16px;">
        <h3>Pincode Page Template Editor</h3>
        <form method="POST">
            <label style="font-weight:600">Title Template</label><br>
            <input type="text" name="title_template" value="<?php echo htmlspecialchars($title_tpl); ?>" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;"><br><br>
            <label style="font-weight:600">Body Template (HTML allowed)</label><br>
            <textarea name="body_template" rows="10" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;"><?php echo htmlspecialchars($body_tpl); ?></textarea>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <br><br>
            <button type="submit" name="save_pincode_template" class="action-btn">Save Template</button>
        </form>
        <p style="color:#666;margin-top:8px;">Placeholders available: <code>{{pincode}}</code>, <code>{{office_name}}</code>, <code>{{district}}</code>, <code>{{statename}}</code>.</p>
    </div>
    <?php
}

// ------------------ Router for admin actions ------------------
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$allowed_actions = ['import','search','post_generator','sitemap','template_pincode'];
$admin_action_output = '';
if ($action !== '') {
    if (!$is_logged_in) {
        $admin_action_output = '<div class="error">Please login to use admin tools.</div>';
    } elseif (!in_array($action, $allowed_actions)) {
        $admin_action_output = '<div class="error">Invalid action.</div>';
    } else {
        ob_start();
        switch ($action) {
            case 'import':
                render_csv_import_form_and_process($pdo);
                break;
            case 'search':
                render_homepage_search_and_popular($pdo);
                break;
            case 'post_generator':
                render_post_generator($pdo);
                break;
            case 'sitemap':
                render_sitemap_tool($pdo);
                break;
            case 'template_pincode':
                render_pincode_template_editor($pdo);
                break;
            default:
                echo '<div class="error">Unhandled action.</div>';
        }
        $admin_action_output = ob_get_clean();
    }
}

// ------------------ HTML (Login + Dashboard) ------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Panel - <?php echo defined('SITE_NAME') ? SITE_NAME : 'PIN Code Website'; ?></title>
    <style>
        /* (Same CSS as before - copied for brevity) */
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh} .login-container{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}.login-box{background:#fff;padding:40px;border-radius:15px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:400px}.login-header{text-align:center;margin-bottom:30px}.login-header h1{color:#667eea;font-size:28px;margin-bottom:10px}.login-header p{color:#666}.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333}.form-group input{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:8px;font-size:16px}.form-group input:focus{outline:none;border-color:#667eea}.btn{width:100%;padding:14px;background:#667eea;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all .3s}.btn:hover{background:#5568d3;transform:translateY(-2px)}.error{background:#fee;color:#c00;padding:12px;border-radius:8px;margin-bottom:20px;text-align:center}.back-link{text-align:center;margin-top:20px}.back-link a{color:#667eea;text-decoration:none}.dashboard{min-height:100vh;background:#f5f5f5}.header{background:#fff;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.1);display:flex;justify-content:space-between;align-items:center}.header-left{display:flex;align-items:center;gap:20px}.logo{font-size:24px;font-weight:bold;color:#667eea}.user-info{display:flex;align-items:center;gap:15px}.logout-btn{padding:8px 20px;background:#dc3545;color:#fff;text-decoration:none;border-radius:5px;font-weight:600}.container{max-width:1400px;margin:0 auto;padding:30px 20px}.welcome{background:#fff;padding:30px;border-radius:10px;margin-bottom:30px}.welcome h2{color:#333;margin-bottom:10px}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}.stat-card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.05)}.stat-icon{font-size:40px;margin-bottom:15px}.stat-number{font-size:32px;font-weight:bold;color:#667eea;margin-bottom:5px}.stat-label{color:#666}.actions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}.action-card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.05);text-align:center}.action-icon{font-size:48px;margin-bottom:15px}.action-title{font-size:18px;font-weight:600;margin-bottom:10px}.action-desc{color:#666;margin-bottom:15px}.action-btn{display:inline-block;padding:10px 25px;background:#667eea;color:#fff;text-decoration:none;border-radius:5px;font-weight:600}.action-btn:hover{background:#5568d3}
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔐 Admin Login</h1>
                <p>PIN Code Website Administration</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" name="login" class="btn">Login</button>
            </form>

            <div class="back-link">
                <a href="/">← Back to Website</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="dashboard">
        <div class="header">
            <div class="header-left">
                <div class="logo">🏛️ Admin Panel</div>
                <a href="/" style="color:#666;text-decoration:none;">View Website →</a>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <form method="POST" style="display:inline-block;margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" name="logout" class="logout-btn" style="border:none;cursor:pointer;">Logout</button>
                </form>
                <a href="?logout=1" style="margin-left:10px;color:#999;text-decoration:none;font-size:12px;">(or click here)</a>
            </div>
        </div>

        <div class="container">
            <div class="welcome">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                <p>Manage your PIN code website from this dashboard</p>
            </div>

            <?php
                // admin action output (if any)
                if (!empty($admin_action_output)) {
                    echo '<div style="margin-bottom:20px;">' . $admin_action_output . '</div>';
                }
            ?>

            <?php
            // Stats
            $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_master");
            $total_pincodes = (int)$stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(DISTINCT statename) FROM pincode_master");
            $total_states = (int)$stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COUNT(DISTINCT district) FROM pincode_master");
            $total_districts = (int)$stmt->fetchColumn();
            $stmt = $pdo->query("SELECT COALESCE(SUM(views_count),0) FROM pincode_master");
            $total_views = (int)$stmt->fetchColumn();
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📮</div>
                    <div class="stat-number"><?php echo number_format($total_pincodes); ?></div>
                    <div class="stat-label">Total PIN Codes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🗺️</div>
                    <div class="stat-number"><?php echo $total_states; ?></div>
                    <div class="stat-label">States</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏘️</div>
                    <div class="stat-number"><?php echo number_format($total_districts); ?></div>
                    <div class="stat-label">Districts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-number"><?php echo number_format($total_views); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>

            <h3 style="margin-bottom:20px;color:#333">Quick Actions</h3>
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">📥</div>
                    <div class="action-title">Import Data</div>
                    <div class="action-desc">Import PIN codes from CSV file</div>
                    <a href="?action=import" class="action-btn">Import CSV</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">🔎</div>
                    <div class="action-title">Search / View Data</div>
                    <div class="action-desc">Search pincodes, filter, export</div>
                    <a href="?action=search" class="action-btn">Open Search Tool</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">✍️</div>
                    <div class="action-title">Post Generator</div>
                    <div class="action-desc">Generate templated posts/pages</div>
                    <a href="?action=post_generator" class="action-btn">Open Post Generator</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">🗺️</div>
                    <div class="action-title">Sitemap / Router</div>
                    <div class="action-desc">Generate or view sitemap routes</div>
                    <a href="?action=sitemap" class="action-btn">Open Sitemap Tool</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">📄</div>
                    <div class="action-title">Pincode Template</div>
                    <div class="action-desc">Edit pincode page template</div>
                    <a href="?action=template_pincode" class="action-btn">Edit Template</a>
                </div>
            </div>

            <div style="background:#fff;padding:25px;border-radius:10px;margin-top:30px;">
                <h3 style="margin-bottom:15px;">📚 Quick Guide</h3>
                <ol style="line-height:2;color:#666">
                    <li><strong>Import PIN Code Data:</strong> Use Import CSV tool.</li>
                    <li><strong>Customize Content:</strong> Edit templates via Pincode Template tool.</li>
                    <li><strong>SEO Settings:</strong> Update seo_meta_templates table.</li>
                    <li><strong>Site Settings:</strong> Modify settings table (site_url etc.).</li>
                    <li><strong>Monitor:</strong> Check total views and popular pages.</li>
                </ol>
            </div>

            <div style="background:#fff3cd;padding:20px;border-radius:10px;margin-top:20px;border-left:4px solid #ffc107;">
                <h4 style="margin-bottom:10px;">⚠️ Important Security Notes:</h4>
                <ul style="line-height:1.8;color:#856404">
                    <li>Never share admin credentials</li>
                    <li>Use strong passwords and 2FA if possible</li>
                    <li>Logout after work</li>
                    <li>Keep DB backups</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
