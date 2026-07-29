<?php
/**
 * includes/auth.php
 * Helper autentikasi dan role management untuk RBAC.
 * Include file ini di awal setiap halaman setelah $base ditetapkan.
 *
 * Cara pakai:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   $isAdmin = isAdmin();
 *   $role = getRole();
 *   $label = getUserLabel();
 */

// Pastikan session sudah dimulai (aman dipanggil berkali-kali)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengembalikan true jika user sudah login sebagai admin.
 * 
 * @return bool True jika admin, false jika open/public
 */
function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Mendapatkan role user saat ini.
 * 
 * @return string 'admin' jika sudah login sebagai admin, 'open' jika belum login (public access)
 */
function getRole(): string {
    return isAdmin() ? 'admin' : 'open';
}

/**
 * Mendapatkan label user untuk ditampilkan di UI.
 * 
 * @return string Label user yang menunjukkan mode akses saat ini
 *                Format: "Admin: [nama]" untuk admin, "Mode: Public" untuk open access
 */
function getUserLabel(): string {
    if (isAdmin()) {
        $adminName = $_SESSION['admin_name'] ?? 'Administrator';
        return "Admin: " . htmlspecialchars($adminName);
    }
    return "Mode: Public";
}

/**
 * Mengecek apakah user dalam mode open (public access).
 * 
 * @return bool True jika dalam mode open/public, false jika admin
 */
function isOpen(): bool {
    return !isAdmin();
}
