<?php
/**
 * PIN CODE WEBSITE - Secure Version
 * Admin panel hidden - only accessible via direct URL
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed. Please contact administrator.');
}

$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';

// Router
if ($route === 'admin' || $route === 'admin/login' || $route === 'admin/dashboard') {
    handleAdmin($pdo);
} elseif (empty($route)) {
    showHomepage($pdo);
} elseif (preg_match('/^(\d{6})$/', $route, $matches)) {
    showPincodeDetail($pdo, $matches[1]);
} elseif ($route === 'search') {
    showSearchResults($pdo);
} elseif (preg_match('/^state\/(.+)$/', $route, $matches)) {
    showStateList($pdo, $matches[1]);
} elseif ($route === 'about') {
    showAboutPage();
} elseif ($route === 'contact') {
    showContactPage();
} else {
    show404();
}

/**
 * Admin Panel - HIDDEN from public
 */
function handleAdmin($pdo) {
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: /admin');
        exit;
    }
    
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel - <?php echo SITE_NAME; ?></title>
        <meta name="robots" content="noindex, nofollow">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .login-container {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header h1 {
                color: #667eea;
                font-size: 28px;
                margin-bottom: 10px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
            }
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
            }
            .btn {
                width: 100%;
                padding: 14px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
            }
            .btn:hover {
                background: #5568d3;
            }
            .error {
                background: #fee;
                color: #c00;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                text-align: center;
            }
            .back-link {
                text-align: center;
                margin-top: 20px;
            }
            .back-link a {
                color: #667eea;
                text-decoration: none;
            }
            .dashboard {
                min-height: 100vh;
                background: #f5f5f5;
            }
            .header {
                background: white;
                padding: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .logo {
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
            }
            .user-info {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .logout-btn {
                padding: 8px 20px;
                background: #dc3545;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
            }
            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 30px 20px;
            }
            .welcome {
                background: white;
                padding: 30px;
                border-radius: 10px;
                margin-bottom: 30px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: white;
                padding: 25px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .stat-icon {
                font-size: 40px;
                margin-bottom: 15px;
            }
            .stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 5px;
            }
            .stat-label {
                color: #666;
            }
            .actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
            .action-card {
                background: white;
                padding: 25px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                text-align: center;
            }
            .action-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .action-title {
                font-size: 18px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .action-desc {
                color: #666;
                margin-bottom: 15px;
            }
            .action-btn {
                display: inline-block;
                padding: 10px 25px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: 600;
            }
            @media (max-width: 768px) {
                .header { flex-direction: column; gap: 15px; }
            }
        </style>
    </head>
    <body>
    
    <?php if (!$is_logged_in): ?>
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <h1>🔐 Admin Login</h1>
                    <p><?php echo SITE_NAME; ?></p>
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
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="logo">🏛️ Admin Panel</div>
                    <a href="/" style="color: #666; text-decoration: none;">View Website →</a>
                </div>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="/admin?logout=1" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <div class="container">
                <div class="welcome">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 👋</h2>
                    <p>Manage your PIN code website</p>
                </div>
                
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_master");
                $total_pincodes = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(DISTINCT statename) FROM pincode_master");
                $total_states = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT COUNT(DISTINCT district) FROM pincode_master");
                $total_districts = $stmt->fetchColumn();
                
                $stmt = $pdo->query("SELECT SUM(views_count) FROM pincode_master");
                $total_views = $stmt->fetchColumn() ?: 0;
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
                
                <h3 style="margin-bottom: 20px; color: #333;">Quick Actions</h3>
                <div class="actions-grid">
                    <div class="action-card">
                        <div class="action-icon">📥</div>
                        <div class="action-title">Import Data</div>
                        <div class="action-desc">Import PIN codes via phpMyAdmin</div>
                        <a href="https://hpanel.hostinger.com" target="_blank" class="action-btn">Open phpMyAdmin</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">📊</div>
                        <div class="action-title">Database</div>
                        <div class="action-desc">Manage database tables</div>
                        <a href="https://hpanel.hostinger.com" target="_blank" class="action-btn">Open cPanel</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">⚙️</div>
                        <div class="action-title">Settings</div>
                        <div class="action-desc">Update settings table</div>
                        <a href="https://hpanel.hostinger.com" target="_blank" class="action-btn">Manage</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">🗂️</div>
                        <div class="action-title">Files</div>
                        <div class="action-desc">File manager</div>
                        <a href="https://hpanel.hostinger.com" target="_blank" class="action-btn">Open</a>
                    </div>
                </div>
                
                <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 4px solid #ffc107;">
                    <h4 style="margin-bottom: 10px; color: #856404;">🔒 Security Notice</h4>
                    <ul style="line-height: 1.8; color: #856404;">
                        <li>Admin URL is hidden from public</li>
                        <li>Only accessible via: www.nrsarthi.com/admin</li>
                        <li>Never share admin credentials</li>
                        <li>Always logout after work</li>
                        <li>Bookmark this page for quick access</li>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    </body>
    </html>
    <?php
    exit;
}

function getHeader($title = '') {
    $site_name = SITE_NAME;
    $page_title = !empty($title) ? $title . ' | ' . $site_name : $site_name;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page_title); ?></title>
        <meta name="description" content="Complete PIN code directory of India. Search and find postal codes for all states, districts, and cities.">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                color: #333;
                background: #f8f9fa;
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                position: sticky;
                top: 0;
                z-index: 1000;
            }
            .top-bar {
                background: rgba(0,0,0,0.1);
                padding: 8px 0;
                font-size: 14px;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
            .top-bar-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .contact-info a {
                color: white;
                text-decoration: none;
                margin-left: 20px;
            }
            .main-nav { padding: 15px 0; }
            .nav-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .logo {
                font-size: 24px;
                font-weight: bold;
                color: white;
                text-decoration: none;
                display: flex;
                align-items: center;
            }
            .logo-icon {
                font-size: 32px;
                margin-right: 10px;
            }
            .nav-menu {
                display: flex;
                list-style: none;
                gap: 30px;
            }
            .nav-menu a {
                color: white;
                text-decoration: none;
                font-weight: 500;
                transition: opacity 0.3s;
            }
            .nav-menu a:hover { opacity: 0.8; }
            .hero {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 60px 20px;
                text-align: center;
            }
            .hero h1 {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .hero p {
                font-size: 20px;
                opacity: 0.9;
            }
            .search-section {
                background: white;
                padding: 40px 20px;
                margin-top: -30px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            }
            .search-box {
                max-width: 700px;
                margin: 0 auto;
            }
            .search-form {
                display: flex;
                gap: 10px;
            }
            .search-input {
                flex: 1;
                padding: 15px 20px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
            }
            .search-input:focus {
                outline: none;
                border-color: #667eea;
            }
            .search-btn {
                padding: 15px 30px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            .search-btn:hover {
                background: #5568d3;
                transform: translateY(-2px);
            }
            .stats {
                padding: 60px 20px;
                background: white;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 30px;
                max-width: 1200px;
                margin: 0 auto;
            }
            .stat-card {
                text-align: center;
                padding: 30px;
                background: #f8f9fa;
                border-radius: 10px;
                transition: all 0.3s;
            }
            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .stat-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .stat-number {
                font-size: 36px;
                font-weight: bold;
                color: #667eea;
                margin-bottom: 10px;
            }
            .stat-label {
                color: #666;
                font-size: 16px;
            }
            .states-section {
                padding: 60px 20px;
                background: #f8f9fa;
            }
            .section-title {
                text-align: center;
                font-size: 36px;
                margin-bottom: 40px;
                color: #333;
            }
            .states-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }
            .state-card {
                background: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                text-decoration: none;
                color: #333;
                transition: all 0.3s;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .state-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                background: #667eea;
                color: white;
            }
            .state-icon {
                font-size: 32px;
                margin-bottom: 10px;
            }
            .features {
                padding: 60px 20px;
                background: white;
            }
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                max-width: 1200px;
                margin: 40px auto 0;
            }
            .feature-card {
                padding: 30px;
                background: #f8f9fa;
                border-radius: 10px;
                text-align: center;
            }
            .feature-icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
            .feature-title {
                font-size: 20px;
                margin-bottom: 10px;
                color: #333;
            }
            .feature-desc {
                color: #666;
            }
            .footer {
                background: #2c3e50;
                color: white;
                padding: 40px 20px 20px;
                margin-top: 60px;
            }
            .footer-content {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 40px;
                margin-bottom: 30px;
            }
            .footer-section h3 {
                margin-bottom: 20px;
                font-size: 20px;
            }
            .footer-section ul {
                list-style: none;
            }
            .footer-section ul li {
                margin-bottom: 10px;
            }
            .footer-section a {
                color: #bdc3c7;
                text-decoration: none;
                transition: color 0.3s;
            }
            .footer-section a:hover {
                color: white;
            }
            .footer-bottom {
                text-align: center;
                padding-top: 20px;
                border-top: 1px solid rgba(255,255,255,0.1);
                color: #bdc3c7;
            }
            .content {
                max-width: 1200px;
                margin: 40px auto;
                padding: 40px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            @media (max-width: 768px) {
                .hero h1 { font-size: 32px; }
                .search-form { flex-direction: column; }
                .nav-menu { display: none; }
                .top-bar-content {
                    flex-direction: column;
                    text-align: center;
                    gap: 10px;
                }
                .contact-info a { margin: 5px 10px; }
            }
        </style>
    </head>
    <body>
        <header class="header">
            <div class="top-bar">
                <div class="container">
                    <div class="top-bar-content">
                        <div>📧 contact@nrsarthi.com</div>
                        <div class="contact-info">
                            <a href="/about">About</a>
                            <a href="/contact">Contact</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <nav class="main-nav">
                <div class="container">
                    <div class="nav-content">
                        <a href="/" class="logo">
                            <span class="logo-icon">🏛️</span>
                            <span><?php echo $site_name; ?></span>
                        </a>
                        <ul class="nav-menu">
                            <li><a href="/">Home</a></li>
                            <li><a href="/search">Search</a></li>
                            <li><a href="/about">About</a></li>
                            <li><a href="/contact">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
    <?php
}

function getFooter() {
    $site_name = SITE_NAME;
    ?>
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>About Us</h3>
                        <p>Complete and accurate PIN code information for all of India.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <li><a href="/search">Search PIN Codes</a></li>
                            <li><a href="/about">About Us</a></li>
                            <li><a href="/contact">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Popular States</h3>
                        <ul>
                            <li><a href="/state/delhi">Delhi PIN Codes</a></li>
                            <li><a href="/state/maharashtra">Maharashtra PIN Codes</a></li>
                            <li><a href="/state/karnataka">Karnataka PIN Codes</a></li>
                            <li><a href="/state/tamil-nadu">Tamil Nadu PIN Codes</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Connect</h3>
                        <ul>
                            <li>📧 contact@nrsarthi.com</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?>. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
    </html>
    <?php
}

function showHomepage($pdo) {
    getHeader();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM pincode_master WHERE is_active = 1");
    $total_pincodes = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT statename) FROM pincode_master");
    $total_states = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT district) FROM pincode_master");
    $total_districts = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT DISTINCT statename FROM pincode_master ORDER BY statename LIMIT 20");
    $states = $stmt->fetchAll(PDO::FETCH_COLUMN);
    ?>
    
    <section class="hero">
        <div class="container">
            <h1>🏛️ India PIN Code Directory</h1>
            <p>Find Complete Postal Information for Any Location in India</p>
        </div>
    </section>
    
    <section class="container">
        <div class="search-section">
            <div class="search-box">
                <form action="/search" method="GET" class="search-form">
                    <input type="text" name="q" class="search-input" placeholder="Search by PIN code, City, District, or State..." required>
                    <button type="submit" class="search-btn">🔍 Search</button>
                </form>
            </div>
        </div>
    </section>
    
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📮</div>
                <div class="stat-number"><?php echo number_format($total_pincodes); ?></div>
                <div class="stat-label">Total PIN Codes</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-number"><?php echo $total_states; ?></div>
                <div class="stat-label">States & UTs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏘️</div>
                <div class="stat-number"><?php echo number_format($total_districts); ?></div>
                <div class="stat-label">Districts</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Always Available</div>
            </div>
        </div>
    </section>
    
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">✅</div>
                    <h3 class="feature-title">Accurate Information</h3>
                    <p class="feature-desc">Complete and verified PIN code data</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🚀</div>
                    <h3 class="feature-title">Fast Search</h3>
                    <p class="feature-desc">Quick results in seconds</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 class="feature-title">Mobile Friendly</h3>
                    <p class="feature-desc">Works on all devices</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🆓</div>
                    <h3 class="feature-title">Free to Use</h3>
                    <p class="feature-desc">Completely free information</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3 class="feature-title">Regular Updates</h3>
                    <p class="feature-desc">Latest information</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3 class="feature-title">Easy Navigation</h3>
                    <p class="feature-desc">User-friendly interface</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="states-section">
        <div class="container">
            <h2 class="section-title">Browse PIN Codes by State</h2>
            <div class="states-grid">
                <?php foreach ($states as $state): ?>
                    <a href="/state/<?php echo urlencode(strtolower(str_replace(' ', '-', $state))); ?>" class="state-card">
                        <div class="state-icon">📍</div>
                        <div><?php echo htmlspecialchars($state); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <?php
    getFooter();
}

function showPincodeDetail($pdo, $pincode) {
    $stmt = $pdo->prepare("SELECT * FROM pincode_master WHERE pincode = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$pincode]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        show404();
        return;
    }
    
    $pdo->prepare("UPDATE pincode_master SET views_count = views_count + 1 WHERE pincode = ?")->execute([$pincode]);
    
    getHeader("PIN Code " . $pincode);
    ?>
    
    <div class="content container">
        <a href="/" style="display: inline-block; padding: 10px 20px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 5px; margin-bottom: 20px;">← Back</a>
        
        <h1 style="color: #667eea; margin-bottom: 10px;">PIN Code: <?php echo $pincode; ?></h1>
        <p style="font-size: 20px; color: #666; margin-bottom: 30px;"><?php echo htmlspecialchars($data['officename']); ?>, <?php echo htmlspecialchars($data['district']); ?>, <?php echo htmlspecialchars($data['statename']); ?></p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <div style="color: #666; margin-bottom: 5px;">Post Office Name</div>
                <div style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($data['officename']); ?></div>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <div style="color: #666; margin-bottom: 5px;">PIN Code</div>
                <div style="font-size: 18px; font-weight: 600;"><?php echo $data['pincode']; ?></div>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <div style="color: #666; margin-bottom: 5px;">District</div>
                <div style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($data['district']); ?></div>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <div style="color: #666; margin-bottom: 5px;">State</div>
                <div style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($data['statename']); ?></div>
            </div>
        </div>
    </div>
    
    <?php
    getFooter();
}

function showSearchResults($pdo) {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query)) {
        header('Location: /');
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM pincode_master 
        WHERE is_active = 1 
        AND (pincode LIKE ? OR officename LIKE ? OR district LIKE ? OR statename LIKE ?)
        LIMIT 100
    ");
    $search_term = "%{$query}%";
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    getHeader("Search: " . $query);
    ?>
    
    <div class="content container">
        <a href="/" style="display: inline-block; padding: 10px 20px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 5px; margin-bottom: 20px;">← Back</a>
        
        <h1 style="color: #667eea;">Search Results</h1>
        <p style="color: #666; margin: 10px 0 30px;">Results for "<?php echo htmlspecialchars($query); ?>"</p>
        
        <?php if (count($results) > 0): ?>
            <p style="margin-bottom: 20px;">Found <?php echo count($results); ?> result(s)</p>
            
            <?php foreach ($results as $row): ?>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                    <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($row['officename']); ?></h3>
                    <p style="color: #666;">📍 <?php echo htmlspecialchars($row['district']); ?>, <?php echo htmlspecialchars($row['statename']); ?></p>
                    <a href="/<?php echo $row['pincode']; ?>" style="display: inline-block; margin-top: 10px; padding: 8px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 20px;">PIN: <?php echo $row['pincode']; ?></a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <h2>No results found</h2>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    getFooter();
}

function showStateList($pdo, $state_slug) {
    $state_name = ucwords(str_replace('-', ' ', $state_slug));
    
    $stmt = $pdo->prepare("SELECT * FROM pincode_master WHERE statename LIKE ? AND is_active = 1 LIMIT 100");
    $stmt->execute(["%{$state_name}%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    getHeader($state_name);
    ?>
    
    <div class="content container">
        <a href="/" style="display: inline-block; padding: 10px 20px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 5px; margin-bottom: 20px;">← Back</a>
        
        <h1 style="color: #667eea;"><?php echo htmlspecialchars($state_name); ?> PIN Codes</h1>
        
        <?php if (count($results) > 0): ?>
            <p style="margin: 10px 0 30px;">Found <?php echo count($results); ?> PIN code(s)</p>
            
            <div style="display: grid; gap: 15px;">
                <?php foreach ($results as $row): ?>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                        <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($row['officename']); ?></h3>
                        <p style="color: #666;">📍 <?php echo htmlspecialchars($row['district']); ?></p>
                        <a href="/<?php echo $row['pincode']; ?>" style="display: inline-block; margin-top: 10px; padding: 8px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 20px;">PIN: <?php echo $row['pincode']; ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="margin-top: 20px;">No PIN codes found.</p>
        <?php endif; ?>
    </div>
    
    <?php
    getFooter();
}

function showAboutPage() {
    getHeader("About");
    ?>
    <div class="content container">
        <h1 style="color: #667eea; margin-bottom: 20px;">About Us</h1>
        <p style="margin-bottom: 15px; line-height: 1.8;">Welcome to India's comprehensive PIN code directory.</p>
    </div>
    <?php
    getFooter();
}

function showContactPage() {
    getHeader("Contact");
    ?>
    <div class="content container">
        <h1 style="color: #667eea; margin-bottom: 20px;">Contact Us</h1>
        <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-top: 20px;">
            <p style="margin-bottom: 15px;">📧 Email: contact@nrsarthi.com</p>
        </div>
    </div>
    <?php
    getFooter();
}

function show404() {
    http_response_code(404);
    getHeader("404");
    ?>
    <div style="text-align: center; padding: 100px 20px;">
        <h1 style="font-size: 72px; color: #667eea;">404</h1>
        <p style="font-size: 20px; color: #666; margin: 20px 0;">Page not found</p>
        <a href="/" style="display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;">← Go Home</a>
    </div>
    <?php
    getFooter();
}
?>