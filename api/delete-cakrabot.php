<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Proteksi Hak Akses Admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak: Hanya admin yang dapat menghapus tiket CakraBot']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID tiket tidak valid']);
    exit;
}

try {
    $count = db_execute("DELETE FROM tickets WHERE id = ?", [$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Tiket CakraBot berhasil dihapus'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Gagal menghapus tiket dari database: ' . $e->getMessage()]);
}
