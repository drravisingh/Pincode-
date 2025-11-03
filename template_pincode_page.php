<?php
// Set page variables for header
$page_title = $post['meta']['title'];
$page_description = $post['meta']['description'];
$page_keywords = $post['meta']['keywords'];
$canonical_url = $post['meta']['canonical'];
$breadcrumb = $post['breadcrumb'];

// Schema markup for header
$schema_markup = [
    $post['schema']['postal_address'],
    $post['schema']['post_office'],
    $post['schema']['faq'],
    $post['schema']['breadcrumb']
];

// Include header
include 'header.php';
?>

<!-- Additional Page Styles -->
<style>
        
        .content-section {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .details-table {
            width: 100%;
            margin: 20px 0;
        }
        
        .details-table tr {
            border-bottom: 1px solid #eee;
        }
        
        .details-table td {
            padding: 12px;
        }
        
        .details-table td:first-child {
            font-weight: 600;
            color: #666;
            width: 30%;
        }
        
        .nearby-card {
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nearby-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .nearby-card h5 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .nearby-card p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .ad-container {
            background: #f8f9fa;
            padding: 10px;
            margin: 20px 0;
            text-align: center;
            border-radius: 8px;
            min-height: 100px;
        }
        
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-delivery {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-non-delivery {
            background: #fff3cd;
            color: #856404;
        }
        
        .faq-item {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .faq-item h4 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        
        @media (max-width: 768px) {
            .content-section {
                padding: 20px 15px;
            }
            
            .details-table td:first-child {
                width: 40%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h1 class="h4 mb-0">📮 India PIN Code</h1>
                </div>
                <div class="col-md-8">
                    <div class="search-box">
                        <form action="/search" method="GET">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" 
                                       placeholder="Search PIN Code, City, or District..." 
                                       required>
                                <button class="btn btn-light" type="submit">🔍 Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Breadcrumb -->
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php foreach ($post['breadcrumb'] as $item): ?>
                    <?php if (empty($item['url'])): ?>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($item['text']); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $item['url']; ?>"><?php echo htmlspecialchars($item['text']); ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column (Main Content) -->
            <div class="col-lg-8">
                <!-- Top Ad (Responsive) -->
                <div class="ad-container">
                    <ins class="adsbygoogle"
                         style="display:block"
                         data-ad-client="ca-pub-XXXXXXXX"
                         data-ad-slot="XXXXXXXX"
                         data-ad-format="auto"
                         data-full-width-responsive="true"></ins>
                    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                </div>
                
                <!-- Main Content Section -->
                <div class="content-section">
                    <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <!-- Introduction -->
                    <p class="lead"><?php echo htmlspecialchars($post['content']['intro']); ?></p>
                    
                    <!-- Details Table -->
                    <h2 class="h4 mt-4 mb-3">📋 PIN Code Details</h2>
                    <table class="details-table">
                        <?php foreach ($post['details_table'] as $label => $value): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($label); ?></td>
                                <td>
                                    <?php if ($label == 'Delivery Status'): ?>
                                        <span class="badge-custom <?php echo $value == 'Delivery' ? 'badge-delivery' : 'badge-non-delivery'; ?>">
                                            <?php echo htmlspecialchars($value); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <!-- About Section -->
                    <h2 class="h4 mt-4 mb-3">ℹ️ About This Post Office</h2>
                    <p><?php echo htmlspecialchars($post['content']['about']); ?></p>
                    
                    <!-- In-Content Ad -->
                    <div class="ad-container my-4">
                        <ins class="adsbygoogle"
                             style="display:block"
                             data-ad-client="ca-pub-XXXXXXXX"
                             data-ad-slot="XXXXXXXX"
                             data-ad-format="auto"
                             data-full-width-responsive="true"></ins>
                        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                    </div>
                    
                    <!-- Delivery Information -->
                    <h2 class="h4 mt-4 mb-3">📦 Delivery Information</h2>
                    <p><?php echo htmlspecialchars($post['content']['delivery_info']); ?></p>
                    
                    <!-- Location Information -->
                    <?php if (isset($post['content']['location'])): ?>
                        <h2 class="h4 mt-4 mb-3">📍 Location Details</h2>
                        <p><?php echo htmlspecialchars($post['content']['location']); ?></p>
                    <?php endif; ?>
                    
                    <!-- Additional Content -->
                    <div class="mt-4">
                        <?php echo $post['content']['additional']; ?>
                    </div>
                </div>
                
                <!-- Map Section -->
                <?php if ($post['map']): ?>
                <div class="content-section">
                    <h2 class="h4 mb-3">🗺️ Location on Map</h2>
                    <div class="map-container">
                        <iframe src="<?php echo $post['map']['embed_url']; ?>" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- FAQ Section -->
                <div class="content-section">
                    <h2 class="h4 mb-3">❓ Frequently Asked Questions</h2>
                    <?php foreach ($post['faq'] as $faq): ?>
                        <div class="faq-item">
                            <h4><?php echo htmlspecialchars($faq['question']); ?></h4>
                            <p class="mb-0"><?php echo htmlspecialchars($faq['answer']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Sidebar Ad (Rectangle) -->
                <div class="ad-container mb-3">
                    <ins class="adsbygoogle"
                         style="display:block"
                         data-ad-client="ca-pub-XXXXXXXX"
                         data-ad-slot="XXXXXXXX"
                         data-ad-format="rectangle"></ins>
                    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                </div>
                
                <!-- Nearby PIN Codes -->
                <div class="content-section">
                    <h3 class="h5 mb-3">📌 Nearby PIN Codes</h3>
                    <?php foreach ($post['nearby_pincodes'] as $nearby): ?>
                        <a href="/pincode/<?php echo $nearby['slug']; ?>" style="text-decoration: none; color: inherit;">
                            <div class="nearby-card">
                                <h5><?php echo htmlspecialchars($nearby['pincode']); ?></h5>
                                <p><?php echo htmlspecialchars($nearby['officename']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($nearby['district']); ?></p>
                                <?php if (isset($nearby['distance_km'])): ?>
                                    <small class="text-muted">📏 <?php echo number_format($nearby['distance_km'], 1); ?> km away</small>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Facilities -->
                <div class="content-section">
                    <h3 class="h5 mb-3">🏢 Available Services</h3>
                    <ul class="list-unstyled">
                        <?php foreach ($post['facilities'] as $facility => $description): ?>
                            <li class="mb-2">
                                <strong><?php echo htmlspecialchars($facility); ?>:</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($description); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Another Sidebar Ad -->
                <div class="ad-container">
                    <ins class="adsbygoogle"
                         style="display:block"
                         data-ad-client="ca-pub-XXXXXXXX"
                         data-ad-slot="XXXXXXXX"
                         data-ad-format="rectangle"></ins>
                    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
                </div>
                
                <!-- Related Links -->
                <div class="content-section mt-3">
                    <h3 class="h5 mb-3">🔗 Related Links</h3>
                    <ul class="list-unstyled">
                        <?php foreach ($post['related_links'] as $link): ?>
                            <li class="mb-2">
                                <a href="<?php echo $link['url']; ?>" class="text-decoration-none">
                                    ➤ <?php echo htmlspecialchars($link['text']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Bottom Ad (Leaderboard) -->
        <div class="ad-container my-4">
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="ca-pub-XXXXXXXX"
                 data-ad-slot="XXXXXXXX"
                 data-ad-format="horizontal"
                 data-full-width-responsive="true"></ins>
            <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Complete PIN code directory for India with detailed information about all post offices.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="/about" class="text-white text-decoration-none">About</a></li>
                        <li><a href="/contact" class="text-white text-decoration-none">Contact</a></li>
                        <li><a href="/privacy" class="text-white text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Resources</h5>
                    <ul class="list-unstyled">
                        <li><a href="https://www.indiapost.gov.in" class="text-white text-decoration-none" target="_blank">India Post</a></li>
                        <li><a href="/tools/pincode-finder" class="text-white text-decoration-none">PIN Code Finder</a></li>
                        <li><a href="/sitemap" class="text-white text-decoration-none">Sitemap</a></li>
                    </ul>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p>&copy; 2025 India PIN Code Directory. All rights reserved.</p>
            </div>
        </div>
    </div>
    

<?php 
// Include footer
include 'footer.php'; 
?>