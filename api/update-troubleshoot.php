<?php
/**
 * API Endpoint: Update Troubleshoot Entry di Notion
 * 
 * Menerima POST request JSON dengan field:
 *   - page_id    (string, required) : ID page Notion yang mau diedit
 *   - kendala    (string, required) : Judul kendala
 *   - bidang     (string, required) : Bidang
 *   - deskripsi  (string, optional) : Deskripsi
 *   - kesimpulan (string, optional) : Kesimpulan penyelesaian
 *   - petugas    (array, required)  : Nama petugas
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

// ===== Validasi & Sanitasi Input =====
$pageId     = trim($input['page_id'] ?? '');
$kendala    = trim($input['kendala'] ?? '');
$bidang     = trim($input['bidang'] ?? '');
$deskripsi  = trim($input['deskripsi'] ?? '');
$kesimpulan = trim($input['kesimpulan'] ?? '');
$petugas    = $input['petugas'] ?? [];

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}
if (empty($kendala)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "kendala" wajib diisi.']);
    exit;
}
if (empty($bidang)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "bidang" wajib dipilih.']);
    exit;
}
if (!is_array($petugas) || count($petugas) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Pilih minimal satu "Nama Petugas".']);
    exit;
}

$validBidang = ['Sarana Prasarana', 'Pemancar', 'Studio'];
if (!in_array($bidang, $validBidang)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bidang tidak valid. Pilihan: ' . implode(', ', $validBidang)]);
    exit;
}

$validPetugas = ['Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Yhanuar','Yudha','Dayat','Saiful','Alif','Winsa','Yayang','Selvy','Alfian'];
foreach ($petugas as $nama) {
    if (!in_array($nama, $validPetugas)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama petugas tidak valid: ' . $nama]);
        exit;
    }
}

// ===== Bangun Payload Notion API =====
// PATCH ke page yang sudah ada (bukan create page baru)
$url = "https://api.notion.com/v1/pages/" . $pageId;

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
];

$petugasPayload = array_map(fn($nama) => ["name" => $nama], $petugas);

$properties = [
    "Kendala" => [
        "title" => [
            ["text" => ["content" => $kendala]]
        ]
    ],
    "Bidang" => [
        "multi_select" => [["name" => $bidang]]
    ],
    "Nama Petugas" => [
        "multi_select" => $petugasPayload
    ],
    // Rich text di-update meskipun kosong, supaya bisa "dikosongkan" saat edit
    "Deskripsi Aktivitas Identifikasi" => [
        "rich_text" => !empty($deskripsi) ? [["text" => ["content" => $deskripsi]]] : []
    ],
    "Kesimpulan Penyelesaian" => [
        "rich_text" => !empty($kesimpulan) ? [["text" => ["content" => $kesimpulan]]] : []
    ],
];

$payload = ["properties" => $properties];

// ===== Kirim PATCH ke Notion API =====
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . $error]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);

if ($httpCode === 200) {
    echo json_encode([
        'success' => true,
        'message' => 'Laporan kendala berhasil diperbarui!',
        'notion_page_id' => $responseData['id'] ?? null,
        'notion_url' => $responseData['url'] ?? null
    ]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}