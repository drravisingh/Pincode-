<?php
declare(strict_types=1);

if (!function_exists('initialize_settings_cache')) {
    function initialize_settings_cache(PDO $pdo, bool $forceReload = false): void
    {
        static $initialised = false;

        if ($initialised && !$forceReload) {
            return;
        }

        try {
            $stmt = $pdo->query('SELECT name, value FROM settings');
            $settings = [];
            if ($stmt !== false) {
                foreach ($stmt as $row) {
                    $settings[$row['name']] = $row['value'];
                }
            }
            $GLOBALS['app_settings'] = $settings;
            $initialised = true;
        } catch (Throwable $exception) {
            $GLOBALS['app_settings'] = [];
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('Settings cache initialisation failed: ' . $exception->getMessage());
            }
        }
    }
}

if (!function_exists('get_app_setting')) {
    function get_app_setting(string $name, ?string $default = null): ?string
    {
        if (!isset($GLOBALS['app_settings']) || !is_array($GLOBALS['app_settings'])) {
            return $default;
        }

        return array_key_exists($name, $GLOBALS['app_settings'])
            ? $GLOBALS['app_settings'][$name]
            : $default;
    }
}

if (!function_exists('get_app_settings')) {
    function get_app_settings(array $names, ?string $default = null): array
    {
        $results = [];
        foreach ($names as $name) {
            $results[$name] = get_app_setting($name, $default);
        }
        return $results;
    }
}

if (!function_exists('set_app_setting')) {
    function set_app_setting(PDO $pdo, string $name, string $value): void
    {
        $stmt = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP');
        $stmt->execute([$name, $value]);
        if (!isset($GLOBALS['app_settings']) || !is_array($GLOBALS['app_settings'])) {
            $GLOBALS['app_settings'] = [];
        }
        $GLOBALS['app_settings'][$name] = $value;
    }
}

if (!function_exists('delete_app_setting')) {
    function delete_app_setting(PDO $pdo, string $name): void
    {
        $stmt = $pdo->prepare('DELETE FROM settings WHERE name = ?');
        $stmt->execute([$name]);
        if (isset($GLOBALS['app_settings'][$name])) {
            unset($GLOBALS['app_settings'][$name]);
        }
    }
}

if (!function_exists('persist_app_setting')) {
    function persist_app_setting(PDO $pdo, string $name, ?string $value): void
    {
        if ($value === null) {
            delete_app_setting($pdo, $name);
        } else {
            set_app_setting($pdo, $name, $value);
        }
    }
}
