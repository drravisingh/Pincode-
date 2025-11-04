<?php
declare(strict_types=1);

/**
 * Render the global site header.
 *
 * @param string $title   Page title fragment (without site name).
 * @param array  $options Additional meta info: description, keywords, canonical, breadcrumb.
 */
function renderHeader(string $title = '', array $options = []): void
{
    $siteName = get_app_setting('site_name', defined('SITE_NAME') ? SITE_NAME : 'India PIN Code Directory');
    $defaultTitle = get_app_setting('seo_default_title', $siteName . ' - Complete Postal Code Directory');
    $defaultDescription = get_app_setting('seo_default_description', 'Find accurate PIN codes for all post offices in India. Complete postal directory with detailed information, maps, and nearby locations.');
    $defaultKeywords = get_app_setting('seo_default_keywords', 'PIN code, postal code, India post office, PIN code finder');

    $page_title = $options['title'] ?? (!empty($title) ? sprintf('%s - %s', $title, $siteName) : $defaultTitle);
    $page_description = $options['description'] ?? $defaultDescription;
    $page_keywords = $options['keywords'] ?? $defaultKeywords;
    $canonical_url = $options['canonical'] ?? ($options['url'] ?? (defined('SITE_URL') ? SITE_URL : ''));
    $breadcrumb = $options['breadcrumb'] ?? [];
    $activeRoute = $options['active'] ?? ($_GET['route'] ?? '');

    $additional_head_html = get_app_setting('seo_additional_head_html', '');
    $structured_data = get_app_setting('seo_structured_data', '');
    $search_console_meta = get_app_setting('search_console_meta_tag');
    $analytics_measurement_id = get_app_setting('analytics_measurement_id');
    $analytics_additional_script = get_app_setting('analytics_additional_script', '');
    $adsense_publisher_id = get_app_setting('adsense_publisher_id');
    $adsense_auto_ads_code = get_app_setting('adsense_auto_ads_code');
    $adsense_strategy_notes = get_app_setting('adsense_strategy_notes', '');

    $adsensePlacements = [
        'top_banner' => get_app_setting('adsense_top_banner', ''),
        'home_featured' => get_app_setting('adsense_home_featured', ''),
        'incontent' => get_app_setting('adsense_incontent_unit', ''),
        'sidebar' => get_app_setting('adsense_sidebar_unit', ''),
        'footer' => get_app_setting('adsense_footer_unit', ''),
    ];

    $GLOBALS['adsense_placements'] = $adsensePlacements;
    $GLOBALS['adsense_strategy_notes'] = $adsense_strategy_notes;

    require __DIR__ . '/../../resources/views/layout/header.php';
}

/**
 * Render the global site footer.
 */
function renderFooter(): void
{
    require __DIR__ . '/../../resources/views/layout/footer.php';
}

/**
 * Render a view file from resources/views.
 *
 * @param string $view Relative view path (e.g. 'pages/about').
 * @param array  $data Data extracted into the view scope.
 */
function renderView(string $view, array $data = []): void
{
    $view = trim($view, '/');
    if ($view === '') {
        throw new InvalidArgumentException('View name cannot be empty.');
    }

    $path = __DIR__ . '/../../resources/views/' . str_replace('..', '', $view) . '.php';

    if (!file_exists($path)) {
        throw new RuntimeException(sprintf('View file not found: %s', $path));
    }

    extract($data, EXTR_SKIP);
    require $path;
}

function renderAdPlacement(string $slot): void
{
    $placements = $GLOBALS['adsense_placements'] ?? [];
    if (!empty($placements[$slot])) {
        echo $placements[$slot];
    }
}
