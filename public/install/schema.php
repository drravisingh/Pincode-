<?php
declare(strict_types=1);

return <<<'SQL'
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
  `role` ENUM('admin', 'editor', 'author') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_username` (`username`),
  UNIQUE KEY `uniq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_setting_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- District metadata
CREATE TABLE IF NOT EXISTS `districts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `state` VARCHAR(150) NOT NULL,
  `population` INT UNSIGNED DEFAULT NULL,
  `area_sq_km` DECIMAL(10,2) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_district_state` (`name`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- States metadata
CREATE TABLE IF NOT EXISTS `states` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `capital` VARCHAR(150) DEFAULT NULL,
  `population` INT UNSIGNED DEFAULT NULL,
  `area_sq_km` DECIMAL(10,2) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_state_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Search analytics
CREATE TABLE IF NOT EXISTS `search_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `query` VARCHAR(255) NOT NULL,
  `results_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_query` (`query`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Saved favourite PIN codes
CREATE TABLE IF NOT EXISTS `favourites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_identifier` VARCHAR(190) NOT NULL,
  `pincode_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_pincode` (`user_identifier`, `pincode_id`),
  CONSTRAINT `fk_favourites_pincode` FOREIGN KEY (`pincode_id`) REFERENCES `pincode_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Content pages
CREATE TABLE IF NOT EXISTS `pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(150) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  `meta_keywords` VARCHAR(255) DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `published_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_page_slug` (`slug`),
  KEY `idx_published` (`is_published`),
  KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Navigation menu
CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `items` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_menu_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import jobs
CREATE TABLE IF NOT EXISTS `import_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `status` ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `total_rows` INT UNSIGNED DEFAULT 0,
  `processed_rows` INT UNSIGNED DEFAULT 0,
  `failed_rows` INT UNSIGNED DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Import job failures
CREATE TABLE IF NOT EXISTS `import_failures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` BIGINT UNSIGNED NOT NULL,
  `row_number` INT UNSIGNED NOT NULL,
  `row_data` JSON DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job_id` (`job_id`),
  CONSTRAINT `fk_import_failures_job` FOREIGN KEY (`job_id`) REFERENCES `import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data sync logs
CREATE TABLE IF NOT EXISTS `sync_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_type` VARCHAR(100) NOT NULL,
  `status` ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cache table
CREATE TABLE IF NOT EXISTS `cache_store` (
  `cache_key` VARCHAR(255) NOT NULL,
  `cache_value` MEDIUMTEXT DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`cache_key`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Newsletter subscribers
CREATE TABLE IF NOT EXISTS `subscribers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `name` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_subscriber_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact messages
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Post office services
CREATE TABLE IF NOT EXISTS `post_office_services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_service_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mapping between PIN codes and services
CREATE TABLE IF NOT EXISTS `pincode_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pincode_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pincode_service` (`pincode_id`, `service_id`),
  CONSTRAINT `fk_pincode_services_pincode` FOREIGN KEY (`pincode_id`) REFERENCES `pincode_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pincode_services_service` FOREIGN KEY (`service_id`) REFERENCES `post_office_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nearby locations cache
CREATE TABLE IF NOT EXISTS `nearby_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pincode_id` INT UNSIGNED NOT NULL,
  `place_name` VARCHAR(255) NOT NULL,
  `place_type` VARCHAR(100) NOT NULL,
  `place_address` VARCHAR(255) DEFAULT NULL,
  `distance_km` DECIMAL(10,2) DEFAULT NULL,
  `raw_response` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pincode_place` (`pincode_id`, `place_type`),
  CONSTRAINT `fk_nearby_locations_pincode` FOREIGN KEY (`pincode_id`) REFERENCES `pincode_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO content calendar
CREATE TABLE IF NOT EXISTS `seo_calendar` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_keyword` VARCHAR(255) NOT NULL,
  `content_type` VARCHAR(100) NOT NULL,
  `status` ENUM('idea','draft','scheduled','published') NOT NULL DEFAULT 'idea',
  `publish_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO outreach tracking
CREATE TABLE IF NOT EXISTS `seo_outreach` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `website` VARCHAR(255) NOT NULL,
  `contact_name` VARCHAR(150) DEFAULT NULL,
  `contact_email` VARCHAR(190) DEFAULT NULL,
  `status` ENUM('new','contacted','negotiating','live','inactive') NOT NULL DEFAULT 'new',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO backlink targets
CREATE TABLE IF NOT EXISTS `seo_backlinks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_url` VARCHAR(255) NOT NULL,
  `anchor_text` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','submitted','live','rejected') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_target_url` (`target_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO focus keywords
CREATE TABLE IF NOT EXISTS `seo_focus_keywords` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(255) NOT NULL,
  `search_volume` INT UNSIGNED DEFAULT NULL,
  `difficulty_score` INT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO structured data presets
CREATE TABLE IF NOT EXISTS `seo_structured_data_presets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `schema_json` JSON NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_structured_data_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Failed jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_type` VARCHAR(100) NOT NULL,
  `payload` JSON DEFAULT NULL,
  `exception_message` TEXT DEFAULT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_password_reset_token` (`token`),
  KEY `idx_password_reset_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
