<?php
/**
 * API Endpoint: Create Troubleshoot Entry ke Notion
 * 
 * Menerima POST request JSON dengan field (sesuai Notion DB schema):
 *   - kendala    (string, required)  : Judul kendala — property "Kendala" (title)
 *   - bidang     (string, required)  : Bidang — property "Bidang" (multi_select)
 *   - deskripsi  (string, optional)  : Deskripsi — property "Deskripsi Aktivitas Identifikasi" (rich_text)
 *   - kesimpulan (string, optional)  : Kesimpulan — property "Kesimpulan Penyelesaian" (rich_text)
 *   - petugas    (array, required)   : Nama petugas — property "Nama Petugas" (multi_select)
 * 
 * Mengirim data ke Notion Database via Notion API (Create Page).
 */

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/../config/notion.php';

// Baca JSON body dari request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body.']);
    exit;
}

// ===== Validasi & Sanitasi Input =====
$kendala    = trim($input['kendala'] ?? '');
$bidang     = trim($input['bidang'] ?? '');
$deskripsi  = trim($input['deskripsi'] ?? '');
$kesimpulan = trim($input['kesimpulan'] ?? '');
$petugas    = $input['petugas'] ?? [];
$dokumentasiUrls = $input['dokumentasi_urls'] ?? []; // Array of {name, url}

// Validasi field wajib
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

// Validasi value bidang
$validBidang = ['Sarana Prasarana', 'Pemancar', 'Studio'];
if (!in_array($bidang, $validBidang)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bidang tidak valid. Pilihan: ' . implode(', ', $validBidang)]);
    exit;
}

// Validasi nama petugas
$validPetugas = ['Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Yhanuar','Yudha','Dayat','Saiful','Alif','Winsa','Yayang','Selvy','Alfian'];
foreach ($petugas as $nama) {
    if (!in_array($nama, $validPetugas)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama petugas tidak valid: ' . $nama]);
        exit;
    }
}

// ===== Bangun Payload Notion API =====
$url = "https://api.notion.com/v1/pages";

$headers = [
    "Authorization: Bearer " . NOTION_TOKEN,
    "Notion-Version: " . NOTION_VERSION,
    "Content-Type: application/json"
];

// Bangun multi_select array untuk Nama Petugas
$petugasPayload = array_map(fn($nama) => ["name" => $nama], $petugas);

// Bangun properties sesuai schema database Notion
$properties = [
    // Kendala (Title)
    "Kendala" => [
        "title" => [
            [
                "text" => [
                    "content" => $kendala
                ]
            ]
        ]
    ],
    // Bidang (Multi Select)
    "Bidang" => [
        "multi_select" => [
            ["name" => $bidang]
        ]
    ],
    // Nama Petugas (Multi Select)
    "Nama Petugas" => [
        "multi_select" => $petugasPayload
    ]
];

// Deskripsi Aktivitas Identifikasi (Rich Text) — hanya jika diisi
if (!empty($deskripsi)) {
    $properties["Deskripsi Aktivitas Identifikasi"] = [
        "rich_text" => [
            [
                "text" => [
                    "content" => $deskripsi
                ]
            ]
        ]
    ];
}

// Kesimpulan Penyelesaian (Rich Text) — hanya jika diisi
if (!empty($kesimpulan)) {
    $properties["Kesimpulan Penyelesaian"] = [
        "rich_text" => [
            [
                "text" => [
                    "content" => $kesimpulan
                ]
            ]
        ]
    ];
}

// Note: "Submission time" adalah created_time (auto-generated oleh Notion)
// Note: "Respondent" adalah created_by (auto-generated oleh Notion)

// Dokumentasi Pendukung (Files) — kirim sebagai external URL jika ada
if (!empty($dokumentasiUrls) && is_array($dokumentasiUrls)) {
    $filesPayload = [];
    foreach ($dokumentasiUrls as $doc) {
        if (!empty($doc['url'])) {
            $filesPayload[] = [
                "name" => $doc['name'] ?? basename($doc['url']),
                "type" => "external",
                "external" => [
                    "url" => $doc['url']
                ]
            ];
        }
    }
    if (!empty($filesPayload)) {
        $properties["Dokumentasi Pendukung"] = [
            "files" => $filesPayload
        ];
    }
}

$payload = [
    "parent" => [
        "database_id" => DATABASE_ID_TROUBLESHOOT
    ],
    "properties" => $properties
];

// ===== Kirim ke Notion API =====
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
    // Sukses! Notion mengembalikan page yang baru dibuat
    echo json_encode([
        'success' => true,
        'message' => 'Laporan kendala berhasil disimpan ke Notion!',
        'notion_page_id' => $responseData['id'] ?? null,
        'notion_url' => $responseData['url'] ?? null
    ]);
} else {
    // Gagal — kirim detail error dari Notion
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => 'Notion API Error (HTTP ' . $httpCode . ')',
        'details' => $responseData['message'] ?? $response
    ]);
}
