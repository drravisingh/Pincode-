<?php
declare(strict_types=1);

function showPincodeDetail(PDO $pdo, string $identifier): void
{
    $identifier = trim($identifier);

    if ($identifier === '') {
        show404();
        return;
    }

    if (preg_match('/^\d{6}$/', $identifier)) {
        $stmt = $pdo->prepare('SELECT * FROM pincode_master WHERE pincode = ? AND is_active = 1 ORDER BY officename LIMIT 1');
        $stmt->execute([$identifier]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM pincode_master WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$identifier]);
    }

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data && preg_match('/^(\d{6})/', $identifier, $matches)) {
        $fallbackPincode = $matches[1];
        $stmt = $pdo->prepare('SELECT * FROM pincode_master WHERE pincode = ? AND is_active = 1 ORDER BY officename LIMIT 1');
        $stmt->execute([$fallbackPincode]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$data) {
        show404();
        return;
    }

    $pincode = $data['pincode'];

    $pdo->prepare('UPDATE pincode_master SET views_count = views_count + 1 WHERE id = ?')->execute([$data['id']]);

    $otherStmt = $pdo->prepare('SELECT id, officename, officetype, delivery, district, statename, slug FROM pincode_master WHERE pincode = ? AND is_active = 1 ORDER BY officename');
    $otherStmt->execute([$pincode]);
    $other_offices = array_filter(
        $otherStmt->fetchAll(PDO::FETCH_ASSOC),
        static fn(array $row): bool => (int) $row['id'] !== (int) $data['id']
    );

    $nearbyStmt = $pdo->prepare('SELECT pincode, officename, district, statename, slug FROM pincode_master WHERE statename = ? AND pincode <> ? AND is_active = 1 ORDER BY RAND() LIMIT 6');
    $nearbyStmt->execute([$data['statename'], $pincode]);
    $nearby_offices = $nearbyStmt->fetchAll(PDO::FETCH_ASSOC);

    $mapsApiKey = get_app_setting('maps_api_key');
    $mapsCategoriesRaw = get_app_setting('maps_nearby_categories', '');
    $maps_categories = array_values(array_filter(array_map(static function ($value) {
        return trim((string) $value);
    }, preg_split('/[\r\n,]+/', (string) $mapsCategoriesRaw) ?: [])));

    $map_embed_url = null;
    $map_query_url = null;
    if (!empty($data['latitude']) && !empty($data['longitude'])) {
        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];
        if ($lat === 0.0 && $lng === 0.0) {
            $lat = $lng = null;
        }
    } else {
        $lat = $lng = null;
    }

    if ($lat !== null && $lng !== null) {
        if ($mapsApiKey) {
            $map_embed_url = sprintf('https://www.google.com/maps/embed/v1/place?key=%s&q=%s,%s&zoom=14', rawurlencode($mapsApiKey), $lat, $lng);
        } else {
            $map_embed_url = sprintf('https://maps.google.com/maps?q=%s,%s&z=14&output=embed', $lat, $lng);
        }
        $map_query_url = sprintf('https://www.google.com/maps/search/%%s/@%s,%s,14z', $lat, $lng);
    }

    $canonicalSlug = !empty($data['slug']) ? $data['slug'] : $pincode;
    $canonical = rtrim(defined('SITE_URL') ? SITE_URL : '', '/') . '/' . $canonicalSlug;

    renderHeader('PIN Code ' . $pincode, [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => $pincode, 'url' => ''],
        ],
        'description' => sprintf('%s PIN code details for %s, %s.', $pincode, $data['officename'], $data['statename']),
        'canonical' => $canonical,
    ]);

    renderView('pincode/detail', compact('pincode', 'data', 'nearby_offices', 'other_offices', 'map_embed_url', 'map_query_url', 'maps_categories'));
    renderFooter();
}

function showSearchResults(PDO $pdo): void
{
    $query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

    if ($query === '') {
        header('Location: /');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM pincode_master
         WHERE is_active = 1
           AND (pincode LIKE ? OR officename LIKE ? OR district LIKE ? OR statename LIKE ?)
         LIMIT 100'
    );

    $search_term = "%{$query}%";
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    renderHeader('Search: ' . $query, [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Search Results', 'url' => ''],
        ],
        'active' => 'search',
    ]);

    renderView('pincode/search', compact('query', 'results'));
    renderFooter();
}

function showStateList(PDO $pdo, string $state_slug): void
{
    $state_name = ucwords(str_replace('-', ' ', $state_slug));

    $stmt = $pdo->prepare('SELECT * FROM pincode_master WHERE statename LIKE ? AND is_active = 1 LIMIT 100');
    $stmt->execute(["%{$state_name}%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    renderHeader($state_name, [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => $state_name, 'url' => ''],
        ],
        'active' => 'states',
    ]);

    renderView('pincode/state', compact('state_name', 'results'));
    renderFooter();
}
