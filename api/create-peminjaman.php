<?php
/**
 * API Endpoint: Create Peminjaman Alat ke Notion
 *
 * Body JSON:
 *   - kegiatan     (string, required) : Nama Kegiatan
 *   - pic          (string, required) : Nama PIC PT
 *   - lokasi       (string, optional)
 *   - tgl_kegiatan (string, optional) : format YYYY-MM-DD
 *   - tgl_pinjam   (string, required) : format YYYY-MM-DD
 *   - durasi       (number, optional)
 *   - keterangan   (string, optional)
 *   - alat         (array, required)  : [{alat, jenis, qty}, ...]
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

$kegiatan     = trim($input['kegiatan'] ?? '');
$pic          = trim($input['pic'] ?? '');
$lokasi       = trim($input['lokasi'] ?? '');
$tglKegiatan  = trim($input['tgl_kegiatan'] ?? '');
$tglPinjam    = trim($input['tgl_pinjam'] ?? '');
$durasi       = $input['durasi'] ?? null;
$keterangan   = trim($input['keterangan'] ?? '');
$alat         = $input['alat'] ?? [];

if (empty($kegiatan)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "kegiatan" wajib diisi.']);
    exit;
}
if (empty($pic)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "pic" wajib diisi.']);
    exit;
}
if (empty($tglPinjam)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "tgl_pinjam" wajib diisi.']);
    exit;
}
if (!is_array($alat) || count($alat) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimal pilih satu alat.']);
    exit;
}

// Bangun "Alat Dipinjam" (multi_select, nama alat unik) & "Detail Alat" (rich_text gabungan)
$alatNames = [];
$detailParts = [];
foreach ($alat as $row) {
    $namaAlat = trim($row['alat'] ?? '');
    $jenis    = trim($row['jenis'] ?? '');
    $qty      = trim((string)($row['qty'] ?? ''));
    if (empty($namaAlat)) continue;

    $alatNames[] = $namaAlat;
    $label = $namaAlat;
    if (!empty($jenis)) $label .= " ({$jenis})";
    if (!empty($qty))   $label .= " x{$qty}";
    $detailParts[] = $label;
}
$alatNames = array_values(array_unique($alatNames));
$detailAlatText = implode(", ", $detailParts);

$multiSelectPayload = array_map(fn($n) => ["name" => $n], $alatNames);

$properties = [
    "Nama Kegiatan" => [
        "title" => [["text" => ["content" => $kegiatan]]]
    ],
    "Nama PIC PT" => [
        "select" => ["name" => $pic]
    ],
    "Alat Dipinjam" => [
        "multi_select" => $multiSelectPayload
    ],
    "Status" => [
        "select" => ["name" => "Dipinjam"]
    ],
];

if (!empty($lokasi)) {
    $properties["Lokasi"] = ["rich_text" => [["text" => ["content" => $lokasi]]]];
}
if (!empty($tglKegiatan)) {
    $properties["Tanggal Kegiatan"] = ["date" => ["start" => $tglKegiatan]];
}
if (!empty($tglPinjam)) {
    $properties["Tanggal Pinjam"] = ["date" => ["start" => $tglPinjam]];
}
if ($durasi !== null && $durasi !== '') {
    $properties["Durasi (jam)"] = ["number" => (float)$durasi];
}
if (!empty($keterangan)) {
    $properties["Keterangan"] = ["rich_text" => [["text" => ["content" => $keterangan]]]];
}
if (!empty($detailAlatText)) {
    $properties["Detail Alat"] = ["rich_text" => [["text" => ["content" => $detailAlatText]]]];
}

$payload = [
    "parent" => ["database_id" => DATABASE_ID_PEMINJAMAN],
    "properties" => $properties
];

$ch = curl_init("https://api.notion.com/v1/pages");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
]);
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
        'message' => 'Peminjaman alat berhasil dicatat!',
        'notion_page_id' => $responseData['id'] ?? null,
    ]);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}