<?php
/**
 * includes/auth.php
 * Helper autentikasi.
 * Include file ini di awal setiap halaman setelah $base ditetapkan.
 *
 * Cara pakai:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   $isAdmin = isAdmin();
 */

// Pastikan session sudah dimulai (aman dipanggil berkali-kali)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengembalikan true jika user sudah login sebagai admin.
 */
function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}
