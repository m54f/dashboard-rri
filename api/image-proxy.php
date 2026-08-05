<?php
$url = $_GET['url'] ?? '';
if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit;
}

// Header cache browser 1 tahun (user fetch sekali, seterusnya pakai cache browser)
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Type: image/jpeg');
header('X-Content-Type-Options: nosniff');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Fetch gambar dari Notion
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$raw) {
    http_response_code(502);
    exit;
}

// Resize on-the-fly tanpa simpan ke disk
if (function_exists('imagecreatefromstring')) {
    $src = @imagecreatefromstring($raw);
    if ($src !== false) {
        $w = imagesx($src);
        $h = imagesy($src);
        $max = 400; // thumbnail cukup untuk Excel
        
        if ($w > $max || $h > $max) {
            $r  = min($max / $w, $max / $h);
            $nw = max(1, (int)($w * $r));
            $nh = max(1, (int)($h * $r));
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }
        
        // Kirim ke browser (tanpa simpan ke disk)
        imagejpeg($src, null, 75);
        imagedestroy($src);
        exit;
    }
}

// Fallback: kirim raw tanpa resize
echo $raw;