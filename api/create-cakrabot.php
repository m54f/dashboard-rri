<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Proteksi Hak Akses Admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak: Hanya admin yang dapat membuat tiket CakraBot']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$description = trim($input['description'] ?? '');
if (empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Deskripsi kendala wajib diisi']);
    exit;
}

$assigneeId = !empty($input['assignee_id']) ? (string)$input['assignee_id'] : null;
$creatorId  = !empty($input['creator_id']) ? (string)$input['creator_id'] : ($assigneeId ?? '111111111');

$statusInput = strtoupper(trim($input['status'] ?? 'OPEN'));
$validStatuses = ['OPEN', 'ASSIGNED', 'RESOLVED'];
$status = in_array($statusInput, $validStatuses) ? $statusInput : 'OPEN';

// Format / Auto-generate ticket code: TCK-YYYYMMDD-001
$ticketCode = trim($input['ticket_code'] ?? '');
if (empty($ticketCode)) {
    $todayStr = date('Ymd');
    $prefix = "TCK-{$todayStr}-";
    try {
        $lastCode = db_value("SELECT ticket_code FROM tickets WHERE ticket_code LIKE ? ORDER BY ticket_code DESC LIMIT 1", [$prefix . '%']);
        if ($lastCode && preg_match('/-(\d+)$/', $lastCode, $matches)) {
            $num = ((int)$matches[1]) + 1;
        } else {
            $num = 1;
        }
        $ticketCode = $prefix . sprintf('%03d', $num);
    } catch (Throwable $e) {
        $ticketCode = $prefix . sprintf('%03d', rand(1, 999));
    }
}

// Timestamps berdasarkan status
$nowStr = date('Y-m-d H:i:s');
$takenAt = ($status === 'ASSIGNED' || $status === 'RESOLVED') ? $nowStr : null;
$resolvedAt = ($status === 'RESOLVED') ? $nowStr : null;

try {
    db_execute(
        "INSERT INTO tickets (ticket_code, description, status, creator_id, assignee_id, created_at, taken_at, resolved_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, NOW())",
        [$ticketCode, $description, $status, $creatorId, $assigneeId, $takenAt, $resolvedAt]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Tiket CakraBot berhasil ditambahkan',
        'ticket_code' => $ticketCode
    ]);
} catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
}
