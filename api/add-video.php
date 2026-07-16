<?php
/**
 * API Endpoint: Add Video Entry ke Notion Database YouTube
 * 
 * Menerima POST request JSON dengan field:
 *   - namaAcaraDisplay (string, required) : Nama Acara / Program
 *   - tema             (string, optional) : Tema tayangan siaran
 *   - judul            (string, required) : Judul gabungan (Nama Acara - Tema)
 *   - tanggal          (string, required) : Tanggal siaran (YYYY-MM-DD)
 *   - jamMulai         (string, required) : Jam mulai siaran
 *   - live             (string, required) : Platform / Tempat Live
 *   - link             (string, optional) : URL Link YouTube video
 *   - penyiar          (string, optional) : String nama penyiar dipisah koma
 *   - narasumber       (string, optional) : String nama narasumber dipisah enter/baris baru
 *   - teknik           (string, optional) : String nama petugas teknik dipisah koma
 *   - pa               (string, optional) : String nama petugas PA dipisah koma
 *   - flyer_urls       (array, optional)  : Array of {name, url} untuk gambar flyer
 */

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

// Baca JSON body dari request sesuai model create-troubleshoot
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

$namaAcaraDisplay = trim($input['namaAcaraDisplay'] ?? '');
$tema             = trim($input['tema'] ?? '');
$judul            = trim($input['judul'] ?? '');
$tanggal          = trim($input['tanggal'] ?? '');
$jamMulai         = trim($input['jamMulai'] ?? '');
$live             = trim($input['live'] ?? '');
$link             = trim($input['link'] ?? '');
$penyiarRaw       = trim($input['penyiar'] ?? '');
$narasumberRaw    = trim($input['narasumber'] ?? '');
$teknikRaw        = trim($input['teknik'] ?? '');
$paRaw            = trim($input['pa'] ?? '');
$flyerUrls        = $input['flyer_urls'] ?? []; // Mengambil manifes berkas [{id, name}]

// Validasi field wajib
if (empty($namaAcaraDisplay) || empty($tanggal) || empty($jamMulai) || empty($live)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nama Acara, Tanggal, Jam Mulai, dan Live Dimana wajib diisi.']);
    exit;
}

// ===== Tentukan slot Pro 1/2/4 dari pilihan "Live Dimana" =====
$slot = null;
$programaLabel = '';
if (strpos($live, 'Pro 1') !== false) {
    $slot = 1;
    $programaLabel = 'Pro 1';
} elseif (strpos($live, 'Pro 2') !== false) {
    $slot = 2;
    $programaLabel = 'Pro 2';
} elseif (strpos($live, 'Pro 4') !== false) {
    $slot = 4;
    $programaLabel = 'Pro 4';
}

if ($slot === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Slot program (Pro 1/2/4) tidak terdeteksi dari pilihan Live Dimana.']);
    exit;
}

// ===== Helper Fungsi Split String ke Multi-Select Array =====
function splitToMultiSelect($raw)
{
    if ($raw === '') return [];
    $parts = array_map('trim', explode(',', $raw));
    $parts = array_filter($parts, fn($v) => $v !== '');
    return array_values(array_map(fn($name) => ['name' => $name], $parts));
}

$penyiarMultiSelect = splitToMultiSelect($penyiarRaw);
$teknikMultiSelect   = splitToMultiSelect($teknikRaw);
$paMultiSelect       = splitToMultiSelect($paRaw);

// ===== Pecah Narasumber (dipisah Enter) ke Narasumber 1/2/3 =====
$narasumberLines = [];
if ($narasumberRaw !== '') {
    $rawLines = preg_split('/\r\n|\r|\n/', $narasumberRaw);
    foreach ($rawLines as $line) {
        $line = trim($line);
        if ($line !== '') $narasumberLines[] = $line;
    }
}

$narasumber1 = '';
$narasumber2 = '';
$narasumber3 = '';

if (count($narasumberLines) <= 3) {
    $narasumber1 = $narasumberLines[0] ?? '';
    $narasumber2 = $narasumberLines[1] ?? '';
    $narasumber3 = $narasumberLines[2] ?? '';
} else {
    $narasumber1 = implode(', ', $narasumberLines);
}

function richTextPayload($text)
{
    if ($text === '') return [];
    return [['type' => 'text', 'text' => ['content' => $text]]];
}

// ===== Bangun Properties Sesuai Schema Database Notion =====
$properties = [
    'Tema Acara' => [
        'title' => richTextPayload($judul !== '' ? $judul : $namaAcaraDisplay)
    ],
    'Programa' => [
        'select' => ['name' => $programaLabel]
    ],
    'Tanggal' => [
        'date' => ['start' => $tanggal]
    ],
    'Jam' => [
        'rich_text' => richTextPayload($jamMulai)
    ],
    "Acara Pro {$slot}" => [
        'select' => ['name' => $namaAcaraDisplay]
    ]
];

if (!empty($link)) {
    $properties['Link Youtube'] = ['url' => $link];
}

if (!empty($penyiarMultiSelect)) {
    $properties["Penyiar Pro {$slot}"] = ['multi_select' => $penyiarMultiSelect];
}

if ($narasumber1 !== '') {
    $properties['Narasumber 1'] = ['rich_text' => richTextPayload($narasumber1)];
}
if ($narasumber2 !== '') {
    $properties['Narasumber 2'] = ['rich_text' => richTextPayload($narasumber2)];
}
if ($narasumber3 !== '') {
    $properties['Narasumber 3'] = ['rich_text' => richTextPayload($narasumber3)];
}

if (!empty($teknikMultiSelect)) {
    $properties['Petugas PT Produksi'] = ['multi_select' => $teknikMultiSelect];
}
if (!empty($paMultiSelect)) {
    $properties['Petugas PT Setting'] = ['multi_select' => $paMultiSelect];
}

// Map payload Flyer (Files) jika ada ID file dari Notion File Upload API
if (!empty($flyerUrls) && is_array($flyerUrls)) {
    $filesPayload = [];
    foreach ($flyerUrls as $doc) {
        if (!empty($doc['id'])) {
            $filesPayload[] = [
                "name" => $doc['name'] ?? 'flyer',
                "type" => "file_upload",
                "file_upload" => [
                    "id" => $doc['id']
                ]
            ];
        }
    }
    if (!empty($filesPayload)) {
        $properties["Flyer"] = [
            "files" => $filesPayload
        ];
    }
}

$payload = [
    "parent" => [
        "database_id" => DATABASE_ID_YOUTUBE
    ],
    "properties" => $properties
];

// ===== Kirim ke Notion API =====
$url = "https://api.notion.com/v1/pages";

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
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
        'message' => 'Video berhasil ditambahkan ke Notion.',
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