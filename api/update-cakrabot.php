<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Proteksi Hak Akses Admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak: Hanya admin yang dapat memperbarui tiket CakraBot']);
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

$description = trim($input['description'] ?? '');
if (empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Deskripsi kendala wajib diisi']);
    exit;
}

$assigneeId = !empty($input['assignee_id']) ? (string)$input['assignee_id'] : null;

$statusInput = strtoupper(trim($input['status'] ?? 'OPEN'));
$validStatuses = ['OPEN', 'ASSIGNED', 'RESOLVED'];
$status = in_array($statusInput, $validStatuses) ? $statusInput : 'OPEN';

try {
    $count = db_execute(
        "UPDATE tickets
         SET description = ?,
             assignee_id = ?,
             status = ?,
             taken_at = CASE WHEN ? IN ('ASSIGNED', 'RESOLVED') AND taken_at IS NULL THEN NOW() ELSE taken_at END,
             resolved_at = CASE WHEN ? = 'RESOLVED' THEN COALESCE(resolved_at, NOW()) ELSE NULL END,
             updated_at = NOW()
         WHERE id = ?",
        [$description, $assigneeId, $status, $status, $status, $id]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Tiket CakraBot berhasil diperbarui'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Gagal memperbarui ke database: ' . $e->getMessage()]);
}
