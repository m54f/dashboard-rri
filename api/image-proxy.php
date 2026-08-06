<?php
// Smart & Ultra-Lightweight Image Proxy with Disk Caching & SVG Fallback
// Mencegah gambar pecah/rusak dari Notion AWS S3 & mempercepat loading on-the-fly

$url = $_GET['url'] ?? '';
$w   = isset($_GET['w']) ? (int)$_GET['w'] : 200;
$w   = min(max($w, 50), 1200); // Batasi 50px - 1200px
$q   = isset($_GET['q']) ? (int)$_GET['q'] : 80;

// Fungsi untuk menampilkan SVG Fallback jika gambar gagal dimuat/kedaluwarsa
function serveSvgFallback() {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-cache, must-revalidate');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <rect width="100%" height="100%" fill="#f1f5f9" rx="12"/>
        <path d="M35 32h30a5 5 0 0 1 5 5v26a5 5 0 0 1-5 5H35a5 5 0 0 1-5-5V37a5 5 0 0 1 5-5zm0 6a2 2 0 0 0-2 2v20l10-10 8 8 7-7 9 9V38a2 2 0 0 0-2-2H35zm9 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" fill="#94a3b8"/>
    </svg>';
    exit;
}

if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    serveSvgFallback();
}

// 1. Ekstrak Kunci Cache yang Stabil dari Path URL (Abaikan parameter signature AWS S3 yang berubah-ubah)
$parsed = parse_url($url);
$pathOnly = $parsed['path'] ?? $url;
$cacheKey = md5($pathOnly . '_' . $w);
$cacheDir = __DIR__ . '/../cache/img';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

$useWebp = function_exists('imagewebp');
$ext = $useWebp ? 'webp' : 'jpg';
$cacheFile = $cacheDir . '/' . $cacheKey . '.' . $ext;

// 2. CEK CACHE DISK (Jika ada, langsung kirim dari disk lokal tanpa request ke Notion/AWS S3!)
if (file_exists($cacheFile) && filesize($cacheFile) > 0) {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Type: ' . ($useWebp ? 'image/webp' : 'image/jpeg'));
    header('X-Content-Type-Options: nosniff');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    readfile($cacheFile);
    exit;
}

// 3. JIKA BELUM ADA DI CACHE DISK, FETCH DARI NOTION / AWS S3
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) LogbookRRI/1.0'
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Jika gagal fetch dari AWS S3 (misal HTTP 403 kedaluwarsa), tampilkan SVG Fallback agar UI tidak rusak
if ($code !== 200 || !$raw) {
    serveSvgFallback();
}

// 4. RESIZE & KOMPRES ON-THE-FLY KE WEBP (Ukuran ~3-10 KB, Super Cepat)
if (function_exists('imagecreatefromstring')) {
    $src = @imagecreatefromstring($raw);
    if ($src !== false) {
        $origW = imagesx($src);
        $origH = imagesy($src);
        
        if ($origW > $w || $origH > $w) {
            $ratio = min($w / $origW, $w / $origH);
            $newW  = max(1, (int)($origW * $ratio));
            $newH  = max(1, (int)($origH * $ratio));
            
            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }
        
        // Simpan ke disk cache & kirim ke browser
        if ($useWebp) {
            imagewebp($src, $cacheFile, $q);
            header('Content-Type: image/webp');
        } else {
            imagejpeg($src, $cacheFile, $q);
            header('Content-Type: image/jpeg');
        }
        imagedestroy($src);
        
        header('Cache-Control: public, max-age=31536000, immutable');
        header('X-Content-Type-Options: nosniff');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        if (file_exists($cacheFile)) {
            readfile($cacheFile);
        } else {
            echo $raw;
        }
        exit;
    }
}

// Fallback jika GD tidak tersedia: kirim raw
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Type: image/jpeg');
echo $raw;