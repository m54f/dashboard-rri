<?php
/**
 * API Endpoint: Update Peminjaman Alat di Notion
 * Body JSON sama seperti create-peminjaman.php, ditambah:
 *   - page_id (string, required)
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

$pageId       = trim($input['page_id'] ?? '');
$kegiatan     = trim($input['kegiatan'] ?? '');
$pic          = trim($input['pic'] ?? '');
$lokasi       = trim($input['lokasi'] ?? '');
$tglKegiatan  = trim($input['tgl_kegiatan'] ?? '');
$tglPinjam    = trim($input['tgl_pinjam'] ?? '');
$durasi       = $input['durasi'] ?? null;
$keterangan   = trim($input['keterangan'] ?? '');
$alat         = $input['alat'] ?? [];

if (empty($pageId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field "page_id" wajib diisi.']);
    exit;
}
if (empty($kegiatan) || empty($pic) || empty($tglPinjam)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Field kegiatan, pic, dan tgl_pinjam wajib diisi.']);
    exit;
}
if (!is_array($alat) || count($alat) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimal pilih satu alat.']);
    exit;
}

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

$properties = [
    "Nama Kegiatan"  => ["title" => [["text" => ["content" => $kegiatan]]]],
    "Nama PIC PT"    => ["select" => ["name" => $pic]],
    "Alat Dipinjam"  => ["multi_select" => array_map(fn($n) => ["name" => $n], $alatNames)],
    "Lokasi"         => ["rich_text" => !empty($lokasi) ? [["text" => ["content" => $lokasi]]] : []],
    "Tanggal Pinjam" => ["date" => ["start" => $tglPinjam]],
    "Durasi (jam)"   => $durasi !== null && $durasi !== '' ? ["number" => (float)$durasi] : ["number" => null],
    "Keterangan"     => ["rich_text" => !empty($keterangan) ? [["text" => ["content" => $keterangan]]] : []],
    "Detail Alat"    => ["rich_text" => !empty($detailAlatText) ? [["text" => ["content" => $detailAlatText]]] : []],
];

$properties["Tanggal Kegiatan"] = !empty($tglKegiatan)
    ? ["date" => ["start" => $tglKegiatan]]
    : ["date" => null];

$payload = ["properties" => $properties];

$ch = curl_init("https://api.notion.com/v1/pages/" . $pageId);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
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
    echo json_encode(['success' => true, 'message' => 'Data peminjaman berhasil diperbarui!']);
} else {
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}