<?php
require_once __DIR__.'/api/youtube.php';

header('Content-Type: application/json');

$videos = getYoutubeVideos();

echo json_encode([
    'success' => true,
    'total_count' => count($videos),
    'is_error' => isset($videos['error']),
    'data' => $videos
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
