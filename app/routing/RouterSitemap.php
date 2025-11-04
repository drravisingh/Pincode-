<?php
/**
 * ================================================
 * URL ROUTING & SITEMAP GENERATION SYSTEM
 * SEO-friendly URLs with automatic sitemap
 * ================================================
 */

class PincodeRouter {
    
    private $db;
    private $generator;
    
    public function __construct($db_connection, $generator) {
        $this->db = $db_connection;
        $this->generator = $generator;
    }
    
    /**
     * Route incoming requests
     */
    public function route($uri) {
        
        // Clean URI
        $uri = trim($uri, '/');
        $parts = explode('/', $uri);
        
        // Homepage
        if (empty($uri)) {
            return $this->renderHomepage();
        }
        
        // Route based on first part
        switch ($parts[0]) {
            case 'pincode':
                if (isset($parts[1])) {
                    return $this->routePincode($parts[1]);
                }
                break;
                
            case 'state':
                if (isset($parts[1])) {
                    return $this->routeState($parts[1]);
                }
                break;
                
            case 'district':
                if (isset($parts[1])) {
                    return $this->routeDistrict($parts[1]);
                }
                break;
                
            case 'nearby':
                if (isset($parts[1])) {
                    return $this->routeNearby($parts[1]);
                }
                break;
                
            case 'search':
                return $this->routeSearch();
                
            case 'sitemap':
                if (isset($parts[1])) {
                    return $this->serveSitemap($parts[1]);
                }
                break;
                
            case 'tools':
                if (isset($parts[1])) {
                    return $this->routeTools($parts[1]);
                }
                break;
        }
        
        // 404
        return $this->render404();
    }
    
    /**
     * Route PIN code page
     */
    private function routePincode($slug) {
        
        // Extract PIN code from slug
        // Slug format: 110001-connaught-place or just 110001
        $pincode = substr($slug, 0, 6);
        
        if (!preg_match('/^\d{6}$/', $pincode)) {
            return $this->render404();
        }
        
        // Generate post
        $post = $this->generator->generatePost($pincode);
        
        if (!$post) {
            return $this->render404();
        }
        
        // Render page
        return $this->renderPincodePage($post);
    }
    
    /**
     * Route state listing page
     */
    private function routeState($state_slug) {
        
        // Get state data
        $sql = "SELECT DISTINCT statename, COUNT(*) as total_pincodes 
                FROM pincode_master 
                WHERE LOWER(REPLACE(statename, ' ', '-')) = ?
                AND is_active = 1
                GROUP BY statename";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$state_slug]);
        $state_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$state_data) {
            return $this->render404();
        }
        
        // Get districts in this state
        $sql = "SELECT DISTINCT district, COUNT(*) as total_pincodes 
                FROM pincode_master 
                WHERE statename = ?
                AND is_active = 1
                GROUP BY district
                ORDER BY district";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$state_data['statename']]);
        $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->renderStatePage($state_data, $districts);
    }
    
    /**
     * Route district listing page
     */
    private function routeDistrict($district_slug) {
        
        // Get district data
        $sql = "SELECT DISTINCT district, statename, COUNT(*) as total_pincodes 
                FROM pincode_master 
                WHERE LOWER(REPLACE(district, ' ', '-')) = ?
                AND is_active = 1
                GROUP BY district, statename";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$district_slug]);
        $district_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$district_data) {
            return $this->render404();
        }
        
        // Get all PIN codes in this district (paginated)
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        
        $sql = "SELECT pincode, officename, delivery, slug 
                FROM pincode_master 
                WHERE district = ?
                AND is_active = 1
                ORDER BY pincode
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$district_data['district'], $per_page, $offset]);
        $pincodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->renderDistrictPage($district_data, $pincodes, $page, $per_page);
    }
    
    /**
     * Route nearby PIN codes
     */
    private function routeNearby($pincode) {
        
        $sql = "SELECT * FROM pincode_master WHERE pincode = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pincode]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return $this->render404();
        }
        
        // Get nearby PIN codes
        $nearby = [];
        if ($data['latitude'] && $data['longitude']) {
            $sql = "SELECT 
                        pm.*,
                        (
                            6371 * acos(
                                cos(radians(?))
                                * cos(radians(latitude))
                                * cos(radians(longitude) - radians(?))
                                + sin(radians(?))
                                * sin(radians(latitude))
                            )
                        ) AS distance_km
                    FROM pincode_master pm
                    WHERE pincode != ?
                    AND latitude IS NOT NULL
                    AND longitude IS NOT NULL
                    AND is_active = 1
                    HAVING distance_km <= 50
                    ORDER BY distance_km
                    LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$data['latitude'], $data['longitude'], $data['latitude'], $pincode]);
            $nearby = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $this->renderNearbyPage($data, $nearby);
    }
    
    /**
     * Route search
     */
    private function routeSearch() {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (empty($query)) {
            return $this->renderSearchPage([]);
        }
        
        // Log search
        $this->logSearch($query);
        
        // Search in database
        $sql = "SELECT * FROM pincode_master 
                WHERE (pincode LIKE ? OR officename LIKE ? OR district LIKE ?)
                AND is_active = 1
                LIMIT 100";
        
        $search_term = "%$query%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $search_term]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->renderSearchPage($results, $query);
    }
    
    /**
     * Homepage
     */
    private function renderHomepage() {
        
        // Get popular states
        $sql = "SELECT statename, COUNT(*) as total_pincodes 
                FROM pincode_master 
                WHERE is_active = 1
                GROUP BY statename 
                ORDER BY total_pincodes DESC 
                LIMIT 10";
        $popular_states = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent searches
        $sql = "SELECT DISTINCT search_query, COUNT(*) as count 
                FROM search_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY search_query 
                ORDER BY count DESC 
                LIMIT 10";
        $recent_searches = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // Get popular PIN codes
        $sql = "SELECT pm.*, pp.views 
                FROM popular_pages pp
                JOIN pincode_master pm ON pp.pincode = pm.pincode
                ORDER BY pp.views DESC
                LIMIT 10";
        $popular_pincodes = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'template' => 'homepage',
            'data' => [
                'popular_states' => $popular_states,
                'recent_searches' => $recent_searches,
                'popular_pincodes' => $popular_pincodes
            ]
        ];
    }
    
    /**
     * Log search query
     */
    private function logSearch($query) {
        $sql = "INSERT INTO search_logs (search_query, ip_address, user_agent) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $query,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Serve sitemap XML
     */
    private function serveSitemap($filename) {
        
        $sitemap_path = __DIR__ . "/sitemaps/$filename";
        
        if (file_exists($sitemap_path)) {
            header('Content-Type: application/xml');
            readfile($sitemap_path);
            exit;
        }
        
        return $this->render404();
    }
    
    /**
     * 404 Page
     */
    private function render404() {
        http_response_code(404);
        return ['template' => '404'];
    }
    
    // Placeholder render functions
    private function renderPincodePage($post) {
        return ['template' => 'pincode', 'data' => $post];
    }
    
    private function renderStatePage($state, $districts) {
        return ['template' => 'state', 'data' => ['state' => $state, 'districts' => $districts]];
    }
    
    private function renderDistrictPage($district, $pincodes, $page, $per_page) {
        return ['template' => 'district', 'data' => ['district' => $district, 'pincodes' => $pincodes, 'page' => $page, 'per_page' => $per_page]];
    }
    
    private function renderNearbyPage($data, $nearby) {
        return ['template' => 'nearby', 'data' => ['pincode' => $data, 'nearby' => $nearby]];
    }
    
    private function renderSearchPage($results, $query = '') {
        return ['template' => 'search', 'data' => ['results' => $results, 'query' => $query]];
    }
    
    private function routeTools($tool) {
        return ['template' => 'tools/' . $tool];
    }
}

/**
 * ================================================
 * SITEMAP GENERATOR
 * Generates multiple sitemap files for SEO
 * ================================================
 */

class SitemapGenerator {
    
    private $db;
    private $base_url;
    private $sitemap_dir;
    private $items_per_file = 50000;
    
    public function __construct($db_connection, $base_url, $sitemap_dir = './sitemaps') {
        $this->db = $db_connection;
        $this->base_url = rtrim($base_url, '/');
        $this->sitemap_dir = $sitemap_dir;
        
        // Create sitemap directory if not exists
        if (!is_dir($sitemap_dir)) {
            mkdir($sitemap_dir, 0755, true);
        }
    }
    
    /**
     * Generate all sitemaps
     */
    public function generateAll() {
        
        echo "Starting sitemap generation...\n";
        
        // Get total PIN codes
        $sql = "SELECT COUNT(*) as total FROM pincode_master WHERE is_active = 1";
        $total = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC)['total'];
        
        $total_files = ceil($total / $this->items_per_file);
        
        echo "Total PIN codes: $total\n";
        echo "Total sitemap files: $total_files\n";
        
        $sitemap_files = [];
        
        // Generate PIN code sitemaps
        for ($i = 0; $i < $total_files; $i++) {
            $offset = $i * $this->items_per_file;
            $filename = "sitemap-pincodes-" . ($i + 1) . ".xml";
            
            $this->generatePincodeSitemap($filename, $offset, $this->items_per_file);
            $sitemap_files[] = $filename;
            
            echo "Generated: $filename\n";
        }
        
        // Generate state sitemap
        $this->generateStateSitemap('sitemap-states.xml');
        $sitemap_files[] = 'sitemap-states.xml';
        echo "Generated: sitemap-states.xml\n";
        
        // Generate district sitemap
        $this->generateDistrictSitemap('sitemap-districts.xml');
        $sitemap_files[] = 'sitemap-districts.xml';
        echo "Generated: sitemap-districts.xml\n";
        
        // Generate static pages sitemap
        $this->generateStaticSitemap('sitemap-static.xml');
        $sitemap_files[] = 'sitemap-static.xml';
        echo "Generated: sitemap-static.xml\n";
        
        // Generate sitemap index
        $this->generateSitemapIndex($sitemap_files);
        echo "Generated: sitemap_index.xml\n";
        
        echo "\n✅ All sitemaps generated successfully!\n";
        
        return true;
    }
    
    /**
     * Generate PIN code sitemap
     */
    private function generatePincodeSitemap($filename, $offset, $limit) {
        
        $sql = "SELECT pincode, slug, updated_at 
                FROM pincode_master 
                WHERE is_active = 1
                ORDER BY pincode
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $pincodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($pincodes as $pin) {
            $url = $this->base_url . '/pincode/' . $pin['slug'];
            $lastmod = date('Y-m-d', strtotime($pin['updated_at']));
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        file_put_contents($this->sitemap_dir . '/' . $filename, $xml);
    }
    
    /**
     * Generate state sitemap
     */
    private function generateStateSitemap($filename) {
        
        $sql = "SELECT DISTINCT statename, MAX(updated_at) as updated_at 
                FROM pincode_master 
                WHERE is_active = 1
                GROUP BY statename";
        
        $states = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($states as $state) {
            $slug = strtolower(str_replace(' ', '-', $state['statename']));
            $url = $this->base_url . '/state/' . $slug;
            $lastmod = date('Y-m-d', strtotime($state['updated_at']));
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.9</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        file_put_contents($this->sitemap_dir . '/' . $filename, $xml);
    }
    
    /**
     * Generate district sitemap
     */
    private function generateDistrictSitemap($filename) {
        
        $sql = "SELECT DISTINCT district, MAX(updated_at) as updated_at 
                FROM pincode_master 
                WHERE is_active = 1
                GROUP BY district";
        
        $districts = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($districts as $district) {
            $slug = strtolower(str_replace(' ', '-', $district['district']));
            $url = $this->base_url . '/district/' . $slug;
            $lastmod = date('Y-m-d', strtotime($district['updated_at']));
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.85</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        file_put_contents($this->sitemap_dir . '/' . $filename, $xml);
    }
    
    /**
     * Generate static pages sitemap
     */
    private function generateStaticSitemap($filename) {
        
        $pages = [
            ['url' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => '/about', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => '/tools/pincode-finder', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($pages as $page) {
            $url = $this->base_url . $page['url'];
            $lastmod = date('Y-m-d');
            
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$page['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';
        
        file_put_contents($this->sitemap_dir . '/' . $filename, $xml);
    }
    
    /**
     * Generate sitemap index
     */
    private function generateSitemapIndex($sitemap_files) {
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($sitemap_files as $file) {
            $url = $this->base_url . '/sitemap/' . $file;
            $lastmod = date('Y-m-d');
            
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>$lastmod</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';
        
        file_put_contents($this->sitemap_dir . '/sitemap_index.xml', $xml);
    }
}

// ================================================
// USAGE EXAMPLE
// ================================================

/*
// Generate sitemaps
$db = new PDO('mysql:host=localhost;dbname=pincode_db', 'username', 'password');
$generator = new SitemapGenerator($db, 'https://yoursite.com', './public/sitemaps');
$generator->generateAll();

// Setup cron job to regenerate weekly:
// 0 2 * * 0 /usr/bin/php /path/to/generate_sitemap.php
*/

?>