<?php
/**
 * API Endpoint: Filter Video Gallery langsung dari Notion Database
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/api-auth.php';
require_once __DIR__ . '/youtube.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET or POST.']);
    exit;
}

$filters = [
    'pt_setting'  => $_REQUEST['pt_setting'] ?? '',
    'pt_produksi' => $_REQUEST['pt_produksi'] ?? '',
    'penyiar'     => $_REQUEST['penyiar'] ?? '',
    'programa'    => $_REQUEST['programa'] ?? '',
];

$videos = getYoutubeVideos($filters);

if (!is_array($videos)) {
    echo json_encode(['success' => false, 'error' => 'Gagal memuat data dari Notion.']);
    exit;
} elseif (isset($videos['error'])) {
    echo json_encode(['success' => false, 'error' => $videos['error']]);
    exit;
}

echo json_encode([
    'success'   => true,
    'data'      => $videos,
    'count'     => count($videos),
    'user_role' => getApiRole()
]);
