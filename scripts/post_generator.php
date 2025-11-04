<?php
/**
 * ================================================
 * DYNAMIC POST GENERATION SYSTEM
 * Generates SEO-friendly pages on-the-fly
 * No need to create 1.65 lakh static posts
 * ================================================
 */

class PincodePostGenerator {
    
    private $db;
    private $cache;
    private $templates;
    
    public function __construct($db_connection, $cache_instance = null) {
        $this->db = $db_connection;
        $this->cache = $cache_instance;
        $this->loadTemplates();
    }
    
    /**
     * Load content templates from database
     */
    private function loadTemplates() {
        $sql = "SELECT * FROM content_templates WHERE is_active = 1";
        $this->templates = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate complete post for a PIN code
     */
    public function generatePost($pincode) {
        
        // Check cache first
        if ($this->cache) {
            $cached = $this->cache->get("post_$pincode");
            if ($cached) {
                return $cached;
            }
        }
        
        // Get PIN code data
        $data = $this->getPincodeData($pincode);
        
        if (!$data) {
            return null;
        }
        
        // Increment view count
        $this->incrementViewCount($pincode);
        
        // Generate different sections
        $post = [
            'meta' => $this->generateMetaTags($data),
            'schema' => $this->generateSchemaMarkup($data),
            'breadcrumb' => $this->generateBreadcrumb($data),
            'title' => $this->generateTitle($data),
            'content' => $this->generateContent($data),
            'details_table' => $this->generateDetailsTable($data),
            'nearby_pincodes' => $this->getNearbyPincodes($data['pincode']),
            'facilities' => $this->generateFacilities($data),
            'faq' => $this->generateFAQ($data),
            'related_links' => $this->getRelatedLinks($data),
            'map' => $this->generateMapEmbed($data)
        ];
        
        // Cache for 24 hours
        if ($this->cache) {
            $this->cache->set("post_$pincode", $post, 86400);
        }
        
        return $post;
    }
    
    /**
     * Get PIN code data from database
     */
    private function getPincodeData($pincode) {
        $sql = "SELECT * FROM pincode_master WHERE pincode = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pincode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate SEO meta tags
     */
    private function generateMetaTags($data) {
        
        // Get meta template
        $sql = "SELECT * FROM seo_meta_templates WHERE page_type = 'pincode_detail' AND is_active = 1 LIMIT 1";
        $template = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if (!$template) {
            // Default meta tags
            return [
                'title' => "{$data['pincode']} PIN Code - {$data['officename']}, {$data['district']}, {$data['statename']}",
                'description' => "Complete information about PIN code {$data['pincode']} for {$data['officename']} in {$data['district']}, {$data['statename']}. Find post office details, delivery status, and nearby PIN codes.",
                'keywords' => "{$data['pincode']}, {$data['officename']}, {$data['district']} PIN code, {$data['statename']} postal code"
            ];
        }
        
        // Replace template variables
        $meta = [
            'title' => $this->replaceVariables($template['title_template'], $data),
            'description' => $this->replaceVariables($template['description_template'], $data),
            'keywords' => $this->replaceVariables($template['keywords_template'], $data),
            'canonical' => $this->generateCanonicalURL($data),
            'og_type' => 'website',
            'og_image' => $this->generateOGImage($data)
        ];
        
        return $meta;
    }
    
    /**
     * Generate Schema.org JSON-LD markup
     */
    private function generateSchemaMarkup($data) {
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'PostalAddress',
            'addressLocality' => $data['officename'],
            'addressRegion' => $data['statename'],
            'postalCode' => $data['pincode'],
            'addressCountry' => 'IN'
        ];
        
        // Add coordinates if available
        if ($data['latitude'] && $data['longitude']) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude']
            ];
        }
        
        // Add PostOffice schema
        $postOfficeSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'PostOffice',
            'name' => $data['officename'],
            'address' => $schema,
            'telephone' => '1800-11-2011', // India Post helpline
            'branchOf' => [
                '@type' => 'Organization',
                'name' => 'India Post'
            ]
        ];
        
        // FAQ Schema
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => "What is the PIN code of {$data['officename']}?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "The PIN code of {$data['officename']} in {$data['district']}, {$data['statename']} is {$data['pincode']}."
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => "What is the post office type?",
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => "{$data['officename']} is a {$data['officetype']} (Branch Office) with {$data['delivery']} status."
                    ]
                ]
            ]
        ];
        
        // Breadcrumb Schema
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => 'https://yoursite.com/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $data['statename'],
                    'item' => "https://yoursite.com/state/{$this->slugify($data['statename'])}"
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $data['district'],
                    'item' => "https://yoursite.com/district/{$this->slugify($data['district'])}"
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => "PIN Code {$data['pincode']}",
                    'item' => "https://yoursite.com/pincode/{$data['slug']}"
                ]
            ]
        ];
        
        return [
            'postal_address' => $schema,
            'post_office' => $postOfficeSchema,
            'faq' => $faqSchema,
            'breadcrumb' => $breadcrumbSchema
        ];
    }
    
    /**
     * Generate page title
     */
    private function generateTitle($data) {
        return "PIN Code {$data['pincode']} - {$data['officename']}, {$data['district']}, {$data['statename']}";
    }
    
    /**
     * Generate main content with template rotation
     */
    private function generateContent($data) {
        
        if (empty($this->templates)) {
            // Fallback content
            return $this->generateDefaultContent($data);
        }
        
        // Select random template based on PIN code (consistent for same PIN)
        $template_index = hexdec(substr(md5($data['pincode']), 0, 8)) % count($this->templates);
        $template = $this->templates[$template_index];
        
        // Generate content sections
        $content = [];
        
        // Introduction
        $content['intro'] = $this->replaceVariables($template['content'], $data);
        
        // Detailed information
        $content['about'] = $this->generateAboutSection($data);
        
        // Delivery information
        $content['delivery_info'] = $this->generateDeliveryInfo($data);
        
        // Location information
        if ($data['latitude'] && $data['longitude']) {
            $content['location'] = $this->generateLocationInfo($data);
        }
        
        // Additional unique content
        $content['additional'] = $this->generateAdditionalContent($data);
        
        return $content;
    }
    
    /**
     * Generate about section
     */
    private function generateAboutSection($data) {
        $office_type_full = [
            'BO' => 'Branch Office',
            'SO' => 'Sub Office',
            'HO' => 'Head Office'
        ];
        
        $type = $office_type_full[$data['officetype']] ?? 'Post Office';
        
        return "The {$data['officename']} is a {$type} located in {$data['district']} district of {$data['statename']}. " .
               "This post office serves the local community with various postal services including mail delivery, " .
               "money orders, and other India Post services. The post office operates under the {$data['divisionname']} " .
               "division and {$data['regionname']} region of India Post.";
    }
    
    /**
     * Generate delivery information
     */
    private function generateDeliveryInfo($data) {
        $delivery_text = $data['delivery'] == 'Delivery' 
            ? "This post office provides home delivery services. Letters, parcels, and courier items are delivered directly to the addresses within the PIN code area."
            : "This is a non-delivery post office. Residents need to collect their mail from the post office counter.";
        
        return "Delivery Status: {$data['delivery']}. $delivery_text " .
               "The post office handles both regular mail and speed post services for domestic and international destinations.";
    }
    
    /**
     * Generate location information
     */
    private function generateLocationInfo($data) {
        return "The geographical coordinates of this area are approximately " .
               "Latitude: {$data['latitude']}° N and Longitude: {$data['longitude']}° E. " .
               "This location information can be used for GPS navigation and finding nearby facilities.";
    }
    
    /**
     * Generate additional unique content
     */
    private function generateAdditionalContent($data) {
        $content = [];
        
        // Services available
        $content[] = "<h3>Postal Services Available</h3>" .
                    "<ul>" .
                    "<li>Regular mail and parcel delivery</li>" .
                    "<li>Speed Post and Express Parcel services</li>" .
                    "<li>Money Order and Instant Money Order</li>" .
                    "<li>Savings Bank and Recurring Deposit accounts</li>" .
                    "<li>Philately products and services</li>" .
                    "<li>Insurance and investment products</li>" .
                    "</ul>";
        
        // Contact information
        $content[] = "<h3>Contact Information</h3>" .
                    "<p>For inquiries about postal services, delivery status, or complaints:</p>" .
                    "<ul>" .
                    "<li>India Post Customer Care: 1800-11-2011 (Toll-Free)</li>" .
                    "<li>Website: <a href='https://www.indiapost.gov.in'>www.indiapost.gov.in</a></li>" .
                    "<li>Complaint Registration: <a href='https://complaints.indiapost.gov.in'>complaints.indiapost.gov.in</a></li>" .
                    "</ul>";
        
        // Tips
        $content[] = "<h3>Important Tips</h3>" .
                    "<ul>" .
                    "<li>Always use correct PIN code for faster delivery</li>" .
                    "<li>Mention complete address including landmark</li>" .
                    "<li>Track your Speed Post at www.indiapost.gov.in</li>" .
                    "<li>Keep proof of posting for valuable items</li>" .
                    "</ul>";
        
        return implode("\n", $content);
    }
    
    /**
     * Generate details table
     */
    private function generateDetailsTable($data) {
        return [
            'PIN Code' => $data['pincode'],
            'Post Office Name' => $data['officename'],
            'Office Type' => $data['officetype'],
            'Delivery Status' => $data['delivery'],
            'District' => $data['district'],
            'State' => $data['statename'],
            'Division' => $data['divisionname'],
            'Region' => $data['regionname'],
            'Circle' => $data['circlename']
        ];
    }
    
    /**
     * Get nearby PIN codes
     */
    private function getNearbyPincodes($pincode, $limit = 10) {
        
        // Check if nearby data exists in cache table
        $sql = "SELECT pm.pincode, pm.officename, pm.district, pm.slug, np.distance_km
                FROM nearby_pincodes np
                JOIN pincode_master pm ON np.nearby_pincode = pm.pincode
                WHERE np.pincode = ?
                ORDER BY np.distance_km
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pincode, $limit]);
        $nearby = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($nearby)) {
            // Fallback: Get nearby from same district
            $sql = "SELECT pincode, officename, district, slug
                    FROM pincode_master
                    WHERE district = (SELECT district FROM pincode_master WHERE pincode = ?)
                    AND pincode != ?
                    AND is_active = 1
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pincode, $pincode, $limit]);
            $nearby = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $nearby;
    }
    
    /**
     * Generate facilities section
     */
    private function generateFacilities($data) {
        return [
            'Banking Services' => 'Post Office Savings Account, Recurring Deposit',
            'Mail Services' => 'Regular Mail, Speed Post, Express Parcel',
            'Financial Services' => 'Money Order, Instant Money Order',
            'Government Schemes' => 'PPF, NSC, KVP, SSY, SCSS',
            'Insurance' => 'Postal Life Insurance, Rural Postal Life Insurance',
            'Other Services' => 'Bill Payment, Mobile Recharge, Aadhaar Services'
        ];
    }
    
    /**
     * Generate FAQ section
     */
    private function generateFAQ($data) {
        return [
            [
                'question' => "What is the PIN code of {$data['officename']}?",
                'answer' => "The PIN code of {$data['officename']} in {$data['district']}, {$data['statename']} is {$data['pincode']}."
            ],
            [
                'question' => "What type of post office is this?",
                'answer' => "{$data['officename']} is a {$data['officetype']} with {$data['delivery']} status."
            ],
            [
                'question' => "Does this post office provide home delivery?",
                'answer' => $data['delivery'] == 'Delivery' 
                    ? "Yes, this post office provides home delivery services for mail and parcels."
                    : "No, this is a non-delivery post office. You need to collect mail from the post office."
            ],
            [
                'question' => "What are the working hours of post offices in India?",
                'answer' => "Most post offices work from Monday to Friday, 10:00 AM to 5:00 PM. Saturday timings are usually 10:00 AM to 1:00 PM. Timings may vary for different branches."
            ],
            [
                'question' => "How can I track my Speed Post?",
                'answer' => "You can track Speed Post on the official India Post website www.indiapost.gov.in or by calling the customer care number 1800-11-2011."
            ]
        ];
    }
    
    /**
     * Get related links
     */
    private function getRelatedLinks($data) {
        return [
            [
                'text' => "All PIN Codes in {$data['district']}",
                'url' => "/district/" . $this->slugify($data['district'])
            ],
            [
                'text' => "{$data['statename']} PIN Codes",
                'url' => "/state/" . $this->slugify($data['statename'])
            ],
            [
                'text' => "Nearby PIN Codes",
                'url' => "/nearby/{$data['pincode']}"
            ],
            [
                'text' => "PIN Code Finder",
                'url' => "/tools/pincode-finder"
            ]
        ];
    }
    
    /**
     * Generate map embed code
     */
    private function generateMapEmbed($data) {
        if (!$data['latitude'] || !$data['longitude']) {
            return null;
        }
        
        // Google Maps embed
        $map_url = "https://maps.google.com/maps?q={$data['latitude']},{$data['longitude']}&z=15&output=embed";
        
        return [
            'embed_url' => $map_url,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude']
        ];
    }
    
    /**
     * Generate breadcrumb
     */
    private function generateBreadcrumb($data) {
        return [
            ['text' => 'Home', 'url' => '/'],
            ['text' => $data['statename'], 'url' => '/state/' . $this->slugify($data['statename'])],
            ['text' => $data['district'], 'url' => '/district/' . $this->slugify($data['district'])],
            ['text' => "PIN {$data['pincode']}", 'url' => '']
        ];
    }
    
    /**
     * Generate canonical URL
     */
    private function generateCanonicalURL($data) {
        return "https://yoursite.com/pincode/{$data['slug']}";
    }
    
    /**
     * Generate OG image
     */
    private function generateOGImage($data) {
        // You can generate dynamic OG images or use a default
        return "https://yoursite.com/images/og-image-default.jpg";
    }
    
    /**
     * Replace template variables
     */
    private function replaceVariables($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
    
    /**
     * Slugify text
     */
    private function slugify($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
    
    /**
     * Generate default content
     */
    private function generateDefaultContent($data) {
        return [
            'intro' => "PIN code {$data['pincode']} belongs to {$data['officename']} in {$data['district']} district of {$data['statename']}. " .
                      "This area is serviced by India Post with {$data['delivery']} status."
        ];
    }
    
    /**
     * Increment view count
     */
    private function incrementViewCount($pincode) {
        $sql = "UPDATE pincode_master SET views_count = views_count + 1 WHERE pincode = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pincode]);
        
        // Also update popular pages
        $sql = "INSERT INTO popular_pages (pincode, page_url, views, last_viewed) 
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE views = views + 1, last_viewed = NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pincode, "/pincode/$pincode"]);
    }
}

// ================================================
// USAGE EXAMPLE
// ================================================

/*
// Database connection
$db = new PDO('mysql:host=localhost;dbname=pincode_db', 'username', 'password');

// Optional: Redis cache
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Create generator
$generator = new PincodePostGenerator($db, $redis);

// Generate post for a PIN code
$post = $generator->generatePost('110001');

if ($post) {
    // Render the page
    echo $post['content']['intro'];
}
*/

?>