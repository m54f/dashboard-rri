<?php
/**
 * Konfigurasi Autentikasi
 * 
 * File ini menyimpan pengaturan autentikasi untuk sistem.
 * Password menggunakan PASSWORD_BCRYPT untuk keamanan.
 */

// Kredensial admin (gunakan password_hash untuk produksi)
// Password default: 'admin123'
// Untuk membuat hash baru, jalankan: password_hash('password_baru', PASSWORD_BCRYPT)
define('ADMIN_CREDENTIALS', [
    'admin' => [
        'username' => 'admin',
        'password_hash' => '$2a$12$ybN8WZduoOC6fAjd.yvb7uj/I899qCnVuEyt/MEd.Og0SzB8BHyni', // TmbMlg
        'name' => 'Administrator',
        'role' => 'admin'
    ]
    // Tambahkan user lain di sini jika diperlukan
    // 'username2' => [
    //     'username' => 'username2',
    //     'password_hash' => 'hash_password',
    //     'name' => 'Nama User',
    //     'role' => 'user'
    // ]
]);

// Pengaturan session
define('SESSION_LIFETIME', 3600); // 1 jam dalam detik
define('SESSION_NAME', 'LOGBOOK_RRI_SESSION');

// Pengaturan rate limiting
define('MAX_LOGIN_ATTEMPTS', 5); // Maksimal 5 percobaan login
define('LOCKOUT_TIME', 900); // 15 menit lockout (dalam detik)

// Pengaturan keamanan
define('ENABLE_CSRF_PROTECTION', true);
define('ENABLE_RATE_LIMITING', true);
define('LOG_LOGIN_ATTEMPTS', true);

// Path untuk log file
define('LOGIN_LOG_FILE', __DIR__ . '/../logs/login.log');
