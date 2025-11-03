-- ================================================
-- PIN CODE WEBSITE - DATABASE SCHEMA (CLEANED)
-- All collation specs removed for compatibility
-- ================================================

-- Main PIN Code Table
CREATE TABLE `pincode_master` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pincode` VARCHAR(10) NOT NULL,
  `officename` VARCHAR(255) NOT NULL,
  `officetype` ENUM('BO', 'SO', 'HO') NOT NULL DEFAULT 'BO',
  `delivery` ENUM('Delivery', 'Non Delivery') NOT NULL DEFAULT 'Delivery',
  `district` VARCHAR(100) NOT NULL,
  `statename` VARCHAR(100) NOT NULL,
  `circlename` VARCHAR(100) NULL,
  `regionname` VARCHAR(100) NULL,
  `divisionname` VARCHAR(100) NULL,
  `latitude` DECIMAL(10, 7) NULL,
  `longitude` DECIMAL(10, 7) NULL,
  `slug` VARCHAR(255) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `views_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_pincode` (`pincode`),
  INDEX `idx_district` (`district`),
  INDEX `idx_state` (`statename`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_officetype` (`officetype`),
  INDEX `idx_delivery` (`delivery`),
  FULLTEXT INDEX `idx_search` (`pincode`, `officename`, `district`, `statename`)
) ENGINE=InnoDB;

-- Content Templates Table
CREATE TABLE `content_templates` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(100) NOT NULL,
  `template_type` ENUM('intro', 'details', 'nearby', 'facilities') NOT NULL,
  `content` TEXT NOT NULL,
  `variables` TEXT COMMENT 'JSON array of available variables',
  `is_active` TINYINT(1) DEFAULT 1,
  `usage_count` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- SEO Meta Templates
CREATE TABLE `seo_meta_templates` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `page_type` VARCHAR(50) NOT NULL,
  `title_template` VARCHAR(255) NOT NULL,
  `description_template` TEXT NOT NULL,
  `keywords_template` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Page Cache Table
CREATE TABLE `page_cache` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cache_key` VARCHAR(255) NOT NULL UNIQUE,
  `page_type` VARCHAR(50) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `meta_data` TEXT COMMENT 'JSON data for SEO',
  `hits` INT(11) DEFAULT 0,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cache_key` (`cache_key`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB;

-- Search Logs
CREATE TABLE `search_logs` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `search_query` VARCHAR(255) NOT NULL,
  `search_type` VARCHAR(50) NULL,
  `results_found` INT(11) DEFAULT 0,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_search_query` (`search_query`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- Popular Pages
CREATE TABLE `popular_pages` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pincode` VARCHAR(10) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `views` INT(11) DEFAULT 0,
  `last_viewed` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pincode` (`pincode`),
  INDEX `idx_views` (`views`)
) ENGINE=InnoDB;

-- Nearby PIN Codes Cache
CREATE TABLE `nearby_pincodes` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pincode` VARCHAR(10) NOT NULL,
  `nearby_pincode` VARCHAR(10) NOT NULL,
  `distance_km` DECIMAL(10, 2) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pincode` (`pincode`),
  INDEX `idx_nearby` (`nearby_pincode`)
) ENGINE=InnoDB;

-- Admin Users
CREATE TABLE `admin_users` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Import History
CREATE TABLE `import_history` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `total_rows` INT(11) DEFAULT 0,
  `imported_rows` INT(11) DEFAULT 0,
  `failed_rows` INT(11) DEFAULT 0,
  `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  `error_log` TEXT NULL,
  `imported_by` INT(11) UNSIGNED NULL,
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`imported_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Settings Table
CREATE TABLE `settings` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `setting_type` VARCHAR(50) DEFAULT 'text',
  `description` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Analytics Table
CREATE TABLE `analytics_daily` (
  `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  `page_views` INT(11) DEFAULT 0,
  `unique_visitors` INT(11) DEFAULT 0,
  `search_queries` INT(11) DEFAULT 0,
  `api_calls` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_date` (`date`)
) ENGINE=InnoDB;

-- ================================================
-- INSERT DEFAULT DATA
-- ================================================

-- Default Content Templates
INSERT INTO `content_templates` (`template_name`, `template_type`, `content`, `variables`) VALUES
('Template 1 - Professional', 'intro', 'PIN code {pincode} serves {officename} in {district} district, {statename}. This {officetype} type post office provides {delivery} status services to the local residents and businesses in the area.', '["pincode","officename","district","statename","officetype","delivery"]'),
('Template 2 - Descriptive', 'intro', 'Looking for postal code of {officename}? The PIN code {pincode} is assigned to this area located in {district}, {statename}. Residents can use this postal code for all their mailing and courier needs.', '["pincode","officename","district","statename"]'),
('Template 3 - Local Focus', 'intro', '{officename} in {district} uses {pincode} as its postal identification code. This area falls under {statename} state and is serviced by India Post with {delivery} facilities.', '["officename","district","pincode","statename","delivery"]'),
('Template 4 - Detailed', 'intro', 'The postal code {pincode} belongs to {officename}, a {officetype} in {district} district of {statename}. This PIN code ensures efficient mail delivery and courier services to residents and establishments in the locality.', '["pincode","officename","officetype","district","statename"]'),
('Template 5 - Area Based', 'intro', 'Residents and businesses in {officename} area of {district} use PIN code {pincode} for all postal communications. This code is part of the {statename} postal circle and maintains {delivery} status.', '["officename","district","pincode","statename","delivery"]');

-- Default SEO Meta Templates
INSERT INTO `seo_meta_templates` (`page_type`, `title_template`, `description_template`) VALUES
('pincode_detail', '{pincode} PIN Code - {officename}, {district}, {statename} | India Post', 'Find complete details of PIN code {pincode} for {officename} in {district}, {statename}. Get address, post office type, delivery status, and nearby PIN codes.'),
('district', '{district} District PIN Codes - {statename} | Complete List', 'Complete list of all PIN codes in {district} district, {statename}. Find post offices, delivery status, and location details.'),
('state', '{statename} PIN Codes - Complete State Postal Code Directory', 'Comprehensive directory of all PIN codes in {statename}. Search post offices, districts, and localities with complete postal information.');

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'India PIN Code Directory', 'text', 'Website name'),
('site_tagline', 'Complete PIN Code Information Portal', 'text', 'Website tagline'),
('items_per_page', '50', 'number', 'Items per page in listing'),
('cache_duration', '86400', 'number', 'Cache duration in seconds (24 hours)'),
('google_maps_api_key', '', 'text', 'Google Maps API Key'),
('enable_caching', '1', 'boolean', 'Enable page caching'),
('auto_generate_sitemap', '1', 'boolean', 'Auto generate sitemap'),
('sitemap_items_per_file', '50000', 'number', 'URLs per sitemap file');

-- ================================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ================================================

CREATE INDEX idx_district_state ON pincode_master(district, statename);
CREATE INDEX idx_state_district ON pincode_master(statename, district);
CREATE INDEX idx_active_delivery ON pincode_master(is_active, delivery);
CREATE INDEX idx_coords ON pincode_master(latitude, longitude);

-- ================================================
-- END OF SCHEMA
-- ================================================