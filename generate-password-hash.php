<?php
/**
 * Password Hash Generator
 * 
 * Script helper untuk generate bcrypt hash untuk config/auth.php
 * 
 * CARA PAKAI:
 * 1. Edit $password di bawah ini dengan password yang Anda inginkan
 * 2. Jalankan: php generate-password-hash.php
 * 3. Copy hash yang dihasilkan ke config/auth.php
 * 4. HAPUS file ini setelah selesai (jangan commit ke git!)
 */

// ============================================
// EDIT PASSWORD DI SINI
// ============================================
$password = 'admin123';  // <- Ganti dengan password Anda
// ============================================

// Generate hash
$hash = password_hash($password, PASSWORD_BCRYPT);

// Output
echo "\n";
echo "====================================\n";
echo "PASSWORD HASH GENERATOR\n";
echo "====================================\n";
echo "\n";
echo "Password yang Anda masukkan:\n";
echo "  " . $password . "\n";
echo "\n";
echo "Hash yang dihasilkan:\n";
echo "  " . $hash . "\n";
echo "\n";
echo "Copy hash di atas ke config/auth.php:\n";
echo "\n";
echo "define('ADMIN_CREDENTIALS', [\n";
echo "    'username' => 'admin',\n";
echo "    'password' => '" . $hash . "'\n";
echo "]);\n";
echo "\n";
echo "====================================\n";
echo "PENTING:\n";
echo "- Hapus file ini setelah selesai\n";
echo "- Jangan commit file ini ke git\n";
echo "====================================\n";
echo "\n";

// Test verification
echo "Test verifikasi password: ";
if (password_verify($password, $hash)) {
    echo "✓ BERHASIL\n";
} else {
    echo "✗ GAGAL\n";
}
echo "\n";
?>
