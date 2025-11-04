-- ======================================================
-- Pincode Directory - Database Schema
-- ======================================================

-- Main PIN code catalogue
CREATE TABLE IF NOT EXISTS `pincode_master` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pincode` VARCHAR(6) NOT NULL,
  `officename` VARCHAR(255) NOT NULL,
  `officetype` VARCHAR(32) NOT NULL DEFAULT 'BO',
  `delivery` VARCHAR(32) NOT NULL DEFAULT 'Delivery',
  `district` VARCHAR(150) NOT NULL,
  `statename` VARCHAR(150) NOT NULL,
  `circlename` VARCHAR(150) DEFAULT NULL,
  `regionname` VARCHAR(150) DEFAULT NULL,
  `divisionname` VARCHAR(150) DEFAULT NULL,
  `contact` VARCHAR(100) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pincode_office` (`pincode`, `officename`),
  UNIQUE KEY `uniq_slug` (`slug`),
  KEY `idx_state` (`statename`),
  KEY `idx_district` (`district`),
  KEY `idx_pincode` (`pincode`),
  KEY `idx_views` (`views_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','editor','viewer') NOT NULL DEFAULT 'editor',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Content templates used by generators and the pincode page editor
CREATE TABLE IF NOT EXISTS `content_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(190) NOT NULL,
  `title_template` VARCHAR(255) NOT NULL,
  `body_template` MEDIUMTEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Generated posts storage (for optional publishing pipelines)
CREATE TABLE IF NOT EXISTS `generated_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` MEDIUMTEXT NOT NULL,
  `pincode` VARCHAR(6) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_generated_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Generic key/value site settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_setting_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional search logging for analytics
CREATE TABLE IF NOT EXISTS `search_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `search_query` VARCHAR(255) NOT NULL,
  `results_found` INT UNSIGNED NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_query` (`search_query`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Minimal import history to track CSV uploads
CREATE TABLE IF NOT EXISTS `import_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `imported_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `error_log` TEXT DEFAULT NULL,
  `imported_by` INT UNSIGNED DEFAULT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_import_history_admin` FOREIGN KEY (`imported_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data --------------------------------------------------------------
INSERT INTO `settings` (`name`, `value`) VALUES
  ('site_name', 'India PIN Code Directory'),
  ('site_tagline', 'Complete PIN code information portal'),
  ('site_url', 'http://localhost'),
  ('seo_default_title', 'India PIN Code Directory - Complete Postal Code Directory'),
  ('seo_default_description', 'Find accurate PIN codes for every Indian post office. Browse verified data, maps, and nearby services.'),
  ('seo_default_keywords', 'pin code, postal code, india post office, pin code finder'),
  ('seo_additional_head_html', ''),
  ('seo_structured_data', ''),
  ('seo_focus_keywords', ''),
  ('seo_backlink_targets', ''),
  ('seo_content_calendar', ''),
  ('seo_outreach_notes', ''),
  ('search_console_meta_tag', ''),
  ('analytics_measurement_id', ''),
  ('analytics_additional_script', ''),
  ('adsense_publisher_id', ''),
  ('adsense_auto_ads_code', ''),
  ('adsense_top_banner', ''),
  ('adsense_home_featured', ''),
  ('adsense_incontent_unit', ''),
  ('adsense_sidebar_unit', ''),
  ('adsense_footer_unit', ''),
  ('adsense_strategy_notes', ''),
  ('maps_api_key', ''),
  ('maps_nearby_categories', 'Post Office\nATM\nBank\nHospital\nPolice Station');

INSERT INTO `content_templates` (`slug`, `title_template`, `body_template`) VALUES
  ('pincode_page', 'PIN Code {{pincode}} — {{officename}}, {{district}}', '<h1>PIN Code {{pincode}}</h1>\n<p>{{officename}} serves {{district}}, {{statename}}.</p>');
