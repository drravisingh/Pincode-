<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($page_title ?? 'India PIN Code Directory - Complete Postal Code Information', ENT_QUOTES); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? 'Find accurate PIN codes for all post offices in India. Complete postal directory with detailed information, maps, and nearby locations.', ENT_QUOTES); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords ?? 'PIN code, postal code, India post office, PIN code finder', ENT_QUOTES); ?>">

    <?php if (!empty($canonical_url)): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url, ENT_QUOTES); ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title ?? 'India PIN Code Directory', ENT_QUOTES); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description ?? 'Complete PIN code information for India', ENT_QUOTES); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url ?? (defined('SITE_URL') ? SITE_URL : ''), ENT_QUOTES); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'India PIN Code Directory', ENT_QUOTES); ?>">

    <?php if (!empty($search_console_meta)): ?>
        <?php if (stripos($search_console_meta, '<meta') !== false): ?>
            <?php echo $search_console_meta; ?>
        <?php else: ?>
            <meta name="google-site-verification" content="<?php echo htmlspecialchars($search_console_meta, ENT_QUOTES); ?>">
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($structured_data)): ?>
        <script type="application/ld+json">
<?php echo $structured_data; ?>
        </script>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <!-- Google AdSense -->
    <?php if (!empty($adsense_auto_ads_code)): ?>
        <?php echo $adsense_auto_ads_code; ?>
    <?php elseif (!empty($adsense_publisher_id)): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars($adsense_publisher_id, ENT_QUOTES); ?>"
                crossorigin="anonymous"></script>
    <?php endif; ?>

    <!-- Google Analytics -->
    <?php if (!empty($analytics_measurement_id)): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($analytics_measurement_id, ENT_QUOTES); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo addslashes($analytics_measurement_id); ?>');
        </script>
    <?php endif; ?>
    <?php if (!empty($analytics_additional_script)): ?>
        <?php echo $analytics_additional_script; ?>
    <?php endif; ?>

    <?php if (!empty($additional_head_html)): ?>
        <?php echo $additional_head_html; ?>
    <?php endif; ?>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .ad-slot {
            margin: 20px auto;
        }

        .ad-slot iframe,
        .ad-slot ins {
            max-width: 100%;
        }

        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-color: #333;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Header Styles */
        .top-bar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 8px 0;
            font-size: 13px;
        }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .top-bar a:hover {
            text-decoration: underline;
        }
        
        .main-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
        }
        
        .logo-icon {
            font-size: 40px;
            margin-right: 12px;
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: var(--primary-color);
        }
        
        .logo-text p {
            font-size: 12px;
            margin: 0;
            color: #666;
        }
        
        .main-nav {
            background: var(--light-bg);
            border-top: 1px solid #dee2e6;
        }
        
        .navbar {
            padding: 0;
        }
        
        .navbar-nav {
            width: 100%;
            justify-content: center;
        }
        
        .nav-link {
            color: var(--text-color) !important;
            padding: 15px 20px !important;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
            border-bottom-color: var(--primary-color);
            background: white;
        }
        
        .search-header {
            max-width: 400px;
        }
        
        .search-header input {
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            padding: 8px 20px;
        }
        
        .search-header button {
            border-radius: 20px;
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 8px 20px;
            margin-left: 5px;
        }

        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
            border-color: var(--primary-color);
        }

        .search-btn {
            padding: 15px 30px;
            background: var(--primary-color);
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
            color: var(--primary-color);
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
            background: var(--primary-color);
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
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .feature-desc { color: #666; }

        .content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Mobile Menu */
        @media (max-width: 768px) {
            .logo-text h1 {
                font-size: 18px;
            }
            
            .logo-icon {
                font-size: 30px;
            }
            
            .nav-link {
                padding: 10px 15px !important;
            }
        }
        
        /* Breadcrumb */
        .breadcrumb-section {
            background: var(--light-bg);
            padding: 10px 0;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 14px;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #666;
        }
    </style>
</head>
<body>
<?php if (!empty($adsensePlacements['top_banner'])): ?>
    <div class="container" style="margin-top:15px;">
        <div class="ad-slot ad-top-banner" style="text-align:center;">
            <?php echo $adsensePlacements['top_banner']; ?>
        </div>
    </div>
<?php endif; ?>

    
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span>📞 India Post Helpline: 1800-11-2011 (Toll Free)</span>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/about">About</a>
                    <a href="/contact">Contact</a>
                    <a href="https://www.indiapost.gov.in" target="_blank">India Post Official</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center logo-section">
                <div class="col-md-4 col-8">
                    <a href="/" class="logo">
                        <div class="logo-icon">📮</div>
                        <div class="logo-text">
                            <h1><?php echo defined('SITE_NAME') ? SITE_NAME : 'India PIN Code'; ?></h1>
                            <p>Complete Postal Directory</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 d-none d-md-block">
                    <form action="/search" method="GET" class="search-header">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="q" 
                                   placeholder="Search PIN Code..." 
                                   required>
                            <button class="btn" type="submit">🔍</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 col-4 text-end">
                    <button class="navbar-toggler d-md-none" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#mainNav"
                            style="border: none; background: var(--primary-color); color: white; padding: 10px 15px; border-radius: 5px;">
                        ☰
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Navigation Menu -->
    <nav class="main-nav">
        <div class="container">
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activeRoute === '' || $activeRoute === 'home') ? 'active' : ''; ?>" href="/">
                            🏠 Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeRoute === 'states' ? 'active' : ''; ?>" href="/#states">
                            📍 Browse by State
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/tools/pincode-finder">
                            🔍 PIN Code Finder
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/tools/distance-calculator">
                            📏 Distance Calculator
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeRoute === 'about' ? 'active' : ''; ?>" href="/about">
                            ℹ️ About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeRoute === 'contact' ? 'active' : ''; ?>" href="/contact">
                            ✉️ Contact
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Search (visible only on mobile) -->
    <div class="d-md-none" style="padding: 15px; background: white; border-bottom: 1px solid #dee2e6;">
        <div class="container">
            <form action="/search" method="GET">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           name="q" 
                           placeholder="Search PIN Code, City, District..." 
                           required>
                    <button class="btn btn-primary" type="submit">🔍 Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Breadcrumb (if exists) -->
    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (empty($item['url'])): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($item['text']); ?>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo $item['url']; ?>">
                                    <?php echo htmlspecialchars($item['text']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content Starts Here -->