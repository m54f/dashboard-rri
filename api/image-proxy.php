<?php
// api/image-proxy.php
 $url = $_GET['url'] ?? '';
if (empty($url)) exit;

// Keamanan: Pastikan hanya mengambil dari URL Notion/AWS
if (strpos($url, 'amazonaws.com') === false && strpos($url, 'notion.com') === false && strpos($url, 'notion.so') === false) {
    exit;
}

header('Content-Type: application/octet-stream');
echo file_get_contents($url);
?>