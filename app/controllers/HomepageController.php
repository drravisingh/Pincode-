<?php
declare(strict_types=1);

function showHomepage(PDO $pdo): void
{
    $total_pincodes = (int) $pdo->query('SELECT COUNT(*) FROM pincode_master WHERE is_active = 1')->fetchColumn();
    $total_states = (int) $pdo->query('SELECT COUNT(DISTINCT statename) FROM pincode_master')->fetchColumn();
    $total_districts = (int) $pdo->query('SELECT COUNT(DISTINCT district) FROM pincode_master')->fetchColumn();

    $stmt = $pdo->query('SELECT DISTINCT statename FROM pincode_master ORDER BY statename LIMIT 20');
    $states = $stmt->fetchAll(PDO::FETCH_COLUMN);

    renderHeader('', [
        'title' => SITE_NAME . ' - Complete Postal Code Directory',
        'description' => 'Search accurate PIN codes for all Indian post offices. Browse by state, district, or city.',
        'active' => 'home',
    ]);

    renderView('pages/home', compact('total_pincodes', 'total_states', 'total_districts', 'states'));
    renderFooter();
}
