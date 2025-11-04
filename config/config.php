<?php
declare(strict_types=1);

// Database configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'pincode_directory');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'pincode_user');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'change_me');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Site configuration
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'India PIN Code Directory');
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600);
}

// Cache configuration
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true);
}
if (!defined('CACHE_DURATION')) {
    define('CACHE_DURATION', 86400);
}

// Optional Redis configuration
if (!defined('REDIS_ENABLED')) {
    define('REDIS_ENABLED', false);
}
if (!defined('REDIS_HOST')) {
    define('REDIS_HOST', '127.0.0.1');
}
if (!defined('REDIS_PORT')) {
    define('REDIS_PORT', 6379);
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'Asia/Kolkata');
}

date_default_timezone_set(APP_TIMEZONE);
