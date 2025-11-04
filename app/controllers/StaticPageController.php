<?php
declare(strict_types=1);

function showAboutPage(): void
{
    renderHeader('About Us', [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'About Us', 'url' => ''],
        ],
        'active' => 'about',
    ]);

    renderView('pages/about');
    renderFooter();
}

function showContactPage(): void
{
    $message_sent = false;
    $error_message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            $error_message = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email address.';
        } else {
            $message_sent = true;
        }
    }

    renderHeader('Contact Us', [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Contact Us', 'url' => ''],
        ],
        'active' => 'contact',
    ]);

    renderView('pages/contact', compact('message_sent', 'error_message'));
    renderFooter();
}

function showPrivacyPolicyPage(): void
{
    renderHeader('Privacy Policy', [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Privacy Policy', 'url' => ''],
        ],
        'active' => 'privacy-policy',
    ]);

    renderView('pages/privacy-policy');
    renderFooter();
}

function showRefundPolicyPage(): void
{
    renderHeader('Refund Policy', [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Refund Policy', 'url' => ''],
        ],
        'active' => 'refund-policy',
    ]);

    renderView('pages/refund-policy');
    renderFooter();
}

function showDisclaimerPage(): void
{
    renderHeader('Disclaimer', [
        'breadcrumb' => [
            ['text' => 'Home', 'url' => '/'],
            ['text' => 'Disclaimer', 'url' => ''],
        ],
        'active' => 'disclaimer',
    ]);

    renderView('pages/disclaimer');
    renderFooter();
}

function show404(): void
{
    http_response_code(404);
    renderHeader('404 - Page Not Found');
    renderView('pages/404');
    renderFooter();
}
