<?php
/**
 * pages/auth/logout.php
 * Hancurkan session dan redirect kembali ke halaman sebelumnya atau dashboard.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

// Redirect ke halaman referer jika ada, atau ke dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

// Pastikan redirect hanya ke halaman lokal (cegah open redirect)
$parsed = parse_url($redirect);
if (!empty($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
    $redirect = '../dashboard.php';
}

header('Location: ' . $redirect);
exit;
