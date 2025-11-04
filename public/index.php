<?php
declare(strict_types=1);

$pdo = require __DIR__ . '/../app/bootstrap.php';

require_once __DIR__ . '/../app/controllers/HomepageController.php';
require_once __DIR__ . '/../app/controllers/PincodeController.php';
require_once __DIR__ . '/../app/controllers/StaticPageController.php';

$route = isset($_GET['route']) ? trim((string) $_GET['route'], '/') : '';

if ($route === 'admin' || $route === 'admin/login' || $route === 'admin/dashboard') {
    require __DIR__ . '/admin/index.php';
    return;
}

if ($route === '') {
    showHomepage($pdo);
    return;
}

if (preg_match('/^\d{6}(?:-[a-z0-9-]+)?$/i', $route)) {
    showPincodeDetail($pdo, $route);
    return;
}

if ($route === 'search') {
    showSearchResults($pdo);
    return;
}

if (strpos($route, 'state/') === 0) {
    $state_slug = substr($route, strlen('state/'));
    showStateList($pdo, $state_slug);
    return;
}

switch ($route) {
    case 'about':
        showAboutPage();
        return;
    case 'contact':
        showContactPage();
        return;
    case 'privacy-policy':
        showPrivacyPolicyPage();
        return;
    case 'refund-policy':
        showRefundPolicyPage();
        return;
    case 'disclaimer':
        showDisclaimerPage();
        return;
}

show404();
