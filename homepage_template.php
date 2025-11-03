<?php
// Set page variables for header
$page_title = defined('SITE_NAME') ? SITE_NAME . ' - Complete Postal Code Information' : 'India PIN Code Directory - Complete Postal Code Information';
$page_description = 'Find PIN codes for all post offices in India. Complete postal code directory with detailed information.';
$page_keywords = 'PIN code, postal code, India post office, PIN code finder';
$canonical_url = defined('SITE_URL') ? SITE_URL : 'https://yoursite.com';

// Include header
include 'header.php';
?>

<!-- Additional Homepage Styles -->
<style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-box input {
            height: 60px;
            font-size: 18px;
            border-radius: 10px 0 0 10px;
            border: none;
        }
        
        .search-box button {
            height: 60px;
            padding: 0 30px;
            font-size: 18px;
            border-radius: 0 10px 10px 0;
            background: #fff;
            color: #667eea;
            border: none;
            font-weight: 600;
        }
        
        .section {
            padding: 60px 0;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 30px;
            height: 100%;
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .state-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .state-list li a {
            display: block;
            padding: 15px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .state-list li a:hover {
            background: #667eea;
            color: white;
        }
        
        .stats {
            background: #f8f9fa;
            padding: 60px 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 30px;
        }
        
        .stat-box h2 {
            font-size: 48px;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-box p {
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>🏛️ India PIN Code Directory</h1>
            <p>Find accurate PIN codes for 1.65+ lakh post offices across India</p>
            
            <form action="/search" method="GET" class="search-box">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" 
                           placeholder="Enter PIN Code, City, District, or State..." 
                           required>
                    <button class="btn" type="submit">🔍 Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Stats Section -->
    <div class="stats">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-box">
                        <h2><?php echo number_format($popular_states[0]['total_pincodes'] ?? 165629); ?></h2>
                        <p>PIN Codes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h2><?php echo count($popular_states ?? []); ?>+</h2>
                        <p>States</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h2>700+</h2>
                        <p>Districts</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <h2>100%</h2>
                        <p>Accurate Data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center mb-5">✨ Features</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <h3>🔍 Quick Search</h3>
                        <p>Find any PIN code instantly by searching for city, district, or post office name.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <h3>📍 Location Details</h3>
                        <p>Get complete information including post office type, delivery status, and exact location.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <h3>🗺️ Interactive Maps</h3>
                        <p>View post office locations on map and find nearby PIN codes with distance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular States -->
    <?php if (!empty($popular_states)): ?>
    <div class="section" style="background: #f8f9fa;">
        <div class="container">
            <h2 class="text-center mb-5">📌 Browse by State</h2>
            <ul class="state-list">
                <?php foreach ($popular_states as $state): ?>
                    <li>
                        <a href="/state/<?php echo strtolower(str_replace(' ', '-', $state['statename'])); ?>">
                            <?php echo htmlspecialchars($state['statename']); ?>
                            <small style="float: right; opacity: 0.7;">
                                <?php echo number_format($state['total_pincodes']); ?> PINs
                            </small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Popular PIN Codes -->
    <?php if (!empty($popular_pincodes)): ?>
    <div class="section">
        <div class="container">
            <h2 class="text-center mb-5">🔥 Popular PIN Codes</h2>
            <div class="row">
                <?php foreach (array_slice($popular_pincodes, 0, 6) as $pin): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <h3><?php echo htmlspecialchars($pin['pincode']); ?></h3>
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($pin['officename']); ?></strong><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($pin['district']); ?>, 
                                    <?php echo htmlspecialchars($pin['statename']); ?>
                                </small>
                            </p>
                            <a href="/pincode/<?php echo $pin['slug']; ?>" 
                               class="btn btn-sm mt-3" 
                               style="background: #667eea; color: white;">View Details →</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    

<?php 
// Include footer
include 'footer.php'; 
?>