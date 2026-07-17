<?php
require_once __DIR__.'/../config/notion.php';

// Notion File Upload API baru butuh Notion-Version yang lebih baru dari yang
// dipakai query/create/update biasa (config/notion.php pakai 2022-06-28,
// itu tetap dipakai untuk semua request lain dan TIDAK diubah supaya tidak
// merusak yang sudah jalan). Khusus untuk upload file, kita pakai versi ini.
define('NOTION_VERSION_FILES', '2025-09-03');

/**
 * Ambil data agenda dari Notion Database Agenda.
 *
 * @param string|null $date Filter tanggal format 'Y-m-d' (opsional). Kalau null, semua data diambil.
 * @param string $sort 'asc' = tanggal+jam terlama dulu (default), 'desc' = terbaru dulu.
 * @return array
 */
function getAgenda($date = null, $sort = 'asc')
{
    $url = "https://api.notion.com/v1/databases/" . DATABASE_ID_AGENDA . "/query";

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode((object)[]));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    $data = json_decode($response, true);

    if (!$data || !isset($data['results'])) {
        return ['error' => 'Invalid API response', 'raw' => $response];
    }

    $agenda = [];

    foreach ($data['results'] as $item) {
        $props = $item['properties'];

        // 1. Judul Kegiatan (Title)
        $title = 'Tanpa Judul';
        foreach (['Nama Kegiatan', 'Kegiatan', 'Judul', 'Judul Kegiatan', 'Title', 'Nama Agenda'] as $key) {
            if (isset($props[$key]['title'][0]['plain_text'])) {
                $title = $props[$key]['title'][0]['plain_text'];
                break;
            }
        }

        // 2. Tanggal & Waktu (Date property)
        $dateRaw = '';
        foreach (['Date', 'Tanggal', 'Tanggal Kegiatan', 'Waktu Kegiatan'] as $key) {
            if (isset($props[$key]['date']['start'])) {
                $dateRaw = $props[$key]['date']['start'];
                break;
            }
        }

        $dateYmd = '';
        $time = '-';
        if ($dateRaw !== '') {
            try {
                $dateObj = new DateTime($dateRaw);
                $dateYmd = $dateObj->format('Y-m-d');
                $time = $dateObj->format('H:i');
            } catch (Exception $e) {
                // biarkan default jika parsing gagal
            }
        }

        if ($date !== null && $dateYmd !== $date) {
            continue;
        }

        // 3. Kategori / Jenis Kegiatan (Select atau Multi Select)
        $cat = 'Umum';
        foreach (['Jenis Kegiatan', 'Kategori', 'Jenis'] as $key) {
            if (isset($props[$key]['select']['name'])) {
                $cat = $props[$key]['select']['name'];
                break;
            }
            if (isset($props[$key]['multi_select'][0]['name'])) {
                $cat = $props[$key]['multi_select'][0]['name'];
                break;
            }
        }
        $tag = $cat;
        $tone = categoryTone($cat);

        // 4. PIC / Lokasi Kegiatan
        $pic = '';
        foreach (['PIC', 'Petugas', 'Nama Petugas'] as $key) {
            if (isset($props[$key]['multi_select']) && !empty($props[$key]['multi_select'])) {
                $pic = implode(', ', array_map(fn($p) => $p['name'], $props[$key]['multi_select']));
                break;
            }
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $pic = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
            if (isset($props[$key]['select']['name'])) {
                $pic = $props[$key]['select']['name'];
                break;
            }
        }
        if ($pic === '') {
            foreach (['Lokasi Kegiatan', 'Lokasi'] as $key) {
                if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                    $pic = $props[$key]['rich_text'][0]['plain_text'];
                    break;
                }
                if (isset($props[$key]['select']['name'])) {
                    $pic = $props[$key]['select']['name'];
                    break;
                }
            }
        }
        if ($pic === '') {
            $pic = 'Belum Ditentukan';
        }

        // 5. Status
        $status = 'Belum';
        if (isset($props['Status']['status']['name'])) {
            $status = $props['Status']['status']['name'];
        } elseif (isset($props['Status']['select']['name'])) {
            $status = $props['Status']['select']['name'];
        }
        $cls = statusClass($status);

        // 6. Catatan / Keterangan tambahan
        $notes = '';
        foreach (['Catatan', 'Keterangan', 'Notes'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $notes = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
        }

        // 7. Dokumentasi / Surat Tugas (Files property)
        $dokumentasi = [];
        foreach (['Surat Tugas / Pengantar (Jika Ada)', 'Surat Tugas / Pengantar', 'Dokumentasi', 'Dokumentasi Pendukung', 'Lampiran'] as $key) {
            if (isset($props[$key]['files']) && !empty($props[$key]['files'])) {
                foreach ($props[$key]['files'] as $file) {
                    $fileUrl = '';
                    $fileName = $file['name'] ?? 'file';
                    if (isset($file['external']['url'])) {
                        $fileUrl = $file['external']['url'];
                    } elseif (isset($file['file']['url'])) {
                        $fileUrl = $file['file']['url'];
                    }
                    if ($fileUrl !== '') {
                        $dokumentasi[] = ['name' => $fileName, 'url' => $fileUrl];
                    }
                }
                break;
            }
        }

        $agenda[] = [
            'id'          => strtoupper(substr($item['id'], 0, 6)),
            'page_id'     => $item['id'],
            'url'         => $item['url'] ?? '#',
            'title'       => $title,
            'tag'         => $tag,
            'tone'        => $tone,
            'pic'         => $pic,
            'status'      => $status,
            'cls'         => $cls,
            'notes'       => $notes,
            'date'        => $dateYmd,
            'time'        => $time,
            'dokumentasi' => $dokumentasi,
        ];
    }

    usort($agenda, function ($a, $b) {
        return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']);
    });
    if ($sort === 'desc') {
        $agenda = array_reverse($agenda);
    }

    return $agenda;
}

function categoryTone($cat)
{
    $c = strtolower($cat);
    if (strpos($c, 'podcast') !== false) return 'blue';
    if (strpos($c, 'live') !== false) return 'purple';
    if (strpos($c, 'produksi') !== false) return 'orange';
    if (strpos($c, 'event') !== false || strpos($c, 'kunjungan') !== false) return 'green';
    return 'gray';
}

function statusClass($status)
{
    $s = strtolower($status);
    if (strpos($s, 'selesai') !== false) return 'green';
    if (strpos($s, 'cancel') !== false) return 'red';
    if (strpos($s, 'proses') !== false) return 'orange';
    if (strpos($s, 'menunggu') !== false || strpos($s, 'belum') !== false) return 'gray';
    return 'blue';
}

/**
 * Langkah 1+2 Notion File Upload API: buat slot upload, lalu kirim isi file.
 * Dokumentasi: https://developers.notion.com/reference/file-upload
 *
 * @return array ['id' => ..., 'name' => ...] kalau sukses, atau ['error' => ...]
 */
function notionUploadFile($tmpPath, $filename, $mimeType)
{
    // Batas ukuran 5MB per file, sama seperti Formulir Notion-nya
    if (filesize($tmpPath) > 5 * 1024 * 1024) {
        return ['error' => "File \"$filename\" lebih dari 5MB"];
    }

    $headersJson = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION_FILES,
        "Content-Type: application/json"
    ];

    // --- Langkah 1: minta slot upload ---
    $ch = curl_init('https://api.notion.com/v1/file_uploads');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headersJson);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'mode'         => 'single_part',
        'filename'     => $filename,
        'content_type' => $mimeType,
    ]));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => $err];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($code !== 200) {
        return ['error' => $result['message'] ?? "HTTP Error: {$code}"];
    }

    $uploadId  = $result['id'];
    $uploadUrl = $result['upload_url'];

    // --- Langkah 2: kirim isi file ke upload_url ---
    $ch2 = curl_init($uploadUrl);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION_FILES,
    ]);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($tmpPath, $mimeType, $filename),
    ]);
    $response2 = curl_exec($ch2);
    if (curl_errno($ch2)) {
        $err = curl_error($ch2);
        curl_close($ch2);
        return ['error' => $err];
    }
    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    $result2 = json_decode($response2, true);
    if ($code2 !== 200) {
        return ['error' => $result2['message'] ?? "HTTP Error: {$code2}"];
    }

    return ['id' => $uploadId, 'name' => $filename];
}

/**
 * Upload banyak file sekaligus dari $_FILES['dokumen'] (bisa array kalau
 * input pakai name="dokumen[]" multiple).
 *
 * @return array [fileObjectsUntukProperty, daftarPesanError]
 */
function notionUploadFiles($filesInput)
{
    $fileObjects = [];
    $errors = [];

    if (!$filesInput || empty($filesInput['name'])) {
        return [$fileObjects, $errors];
    }

    $names = $filesInput['name'];
    $isMultiple = is_array($names);
    $count = $isMultiple ? count($names) : 1;

    for ($i = 0; $i < $count; $i++) {
        $name = $isMultiple ? $filesInput['name'][$i] : $filesInput['name'];
        $tmp  = $isMultiple ? $filesInput['tmp_name'][$i] : $filesInput['tmp_name'];
        $type = $isMultiple ? $filesInput['type'][$i] : $filesInput['type'];
        $err  = $isMultiple ? $filesInput['error'][$i] : $filesInput['error'];

        if ($name === '' || $err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = "$name: gagal diupload dari browser (kode error $err)";
            continue;
        }

        $uploaded = notionUploadFile($tmp, $name, $type ?: 'application/octet-stream');
        if (isset($uploaded['error'])) {
            $errors[] = "$name: " . $uploaded['error'];
            continue;
        }

        $fileObjects[] = [
            'name'        => $uploaded['name'],
            'type'        => 'file_upload',
            'file_upload' => ['id' => $uploaded['id']],
        ];
    }

    return [$fileObjects, $errors];
}

/**
 * Susun properties dasar (judul, tanggal, lokasi, kategori, status) yang
 * dipakai bareng oleh createAgenda() dan updateAgenda().
 */
function buildAgendaBaseProperties($data)
{
    $judul    = trim($data['kegiatan'] ?? '');
    $tanggal  = trim($data['tanggal'] ?? '');
    $waktu    = trim($data['waktu'] ?? '');
    $lokasi   = trim($data['lokasi_kegiatan'] ?? $data['pic'] ?? '');
    $kategori = trim($data['kategori'] ?? 'EVENT');
    $status   = trim($data['status'] ?? 'Belum');

    if ($judul === '' || $tanggal === '' || $waktu === '') {
        return ['error' => 'Judul, tanggal, dan waktu wajib diisi'];
    }

    $dateTimeStr = $tanggal . 'T' . $waktu . ':00+07:00';

    // ==== SESUAIKAN nama property di sini kalau beda dengan Notion-mu ====
    return [
        'properties' => [
            'Nama Kegiatan' => [
                'title' => [
                    ['text' => ['content' => $judul]]
                ]
            ],
            'Date' => [
                'date' => ['start' => $dateTimeStr]
            ],
            'Lokasi Kegiatan' => [
                'rich_text' => [
                    ['text' => ['content' => $lokasi]]
                ]
            ],
            'Jenis Kegiatan' => [
                'multi_select' => [
                    ['name' => $kategori]
                ]
            ],
            'Status' => [
                'select' => ['name' => $status]
            ],
        ]
    ];
}

/**
 * Buat page/agenda baru di Notion dari data form.
 *
 * @param array $data Data dari $_POST
 * @param array|null $filesInput Data dari $_FILES['dokumen'] (opsional)
 * @return array
 */
function createAgenda($data, $filesInput = null)
{
    $built = buildAgendaBaseProperties($data);
    if (isset($built['error'])) {
        return $built;
    }
    $properties = $built['properties'];

    [$fileObjects, $fileErrors] = notionUploadFiles($filesInput);
    if (!empty($fileObjects)) {
        // Gunakan nama property yang umum di Database Notion: "Surat Tugas / Pengantar"
        $properties['Surat Tugas / Pengantar'] = ['files' => $fileObjects];
    }

    $payload = json_encode([
        'parent'     => ['database_id' => DATABASE_ID_AGENDA],
        'properties' => $properties,
    ]);

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init("https://api.notion.com/v1/pages");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    $out = ['success' => true, 'id' => $result['id'] ?? null];
    if (!empty($fileErrors)) {
        $out['file_warnings'] = $fileErrors;
    }
    if (!empty($fileObjects)) {
        $out['uploaded_files'] = array_map(fn($f) => $f['name'] ?? null, $fileObjects);
    }
    return $out;
}

/**
 * Update page agenda yang sudah ada.
 *
 * PENTING soal dokumen: kalau kamu upload file baru saat edit, file yang
 * LAMA akan TERGANTI (Notion tidak punya "tambah ke file yang sudah ada"
 * lewat API, cuma bisa timpa seluruh isi property). Kalau tidak upload
 * file baru sama sekali, dokumen lama tetap aman karena property-nya
 * tidak ikut dikirim di request update.
 *
 * @return array
 */
function updateAgenda($pageId, $data, $filesInput = null)
{
    if (!$pageId) {
        return ['error' => 'page_id wajib diisi'];
    }

    $built = buildAgendaBaseProperties($data);
    if (isset($built['error'])) {
        return $built;
    }
    $properties = $built['properties'];

    [$fileObjects, $fileErrors] = notionUploadFiles($filesInput);
    if (!empty($fileObjects)) {
        $properties['Surat Tugas / Pengantar'] = ['files' => $fileObjects];
    }

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init("https://api.notion.com/v1/pages/" . $pageId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['properties' => $properties]));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    $out = ['success' => true, 'id' => $pageId];
    if (!empty($fileErrors)) {
        $out['file_warnings'] = $fileErrors;
    }
    if (!empty($fileObjects)) {
        $out['uploaded_files'] = array_map(fn($f) => $f['name'] ?? null, $fileObjects);
    }
    return $out;
}

/**
 * "Hapus" agenda. Notion API tidak benar-benar menghapus page lewat API
 * publik — yang dilakukan adalah archive (dipindah ke Trash Notion),
 * persis seperti kalau kamu hapus manual dari Notion.
 *
 * @return array
 */
function deleteAgenda($pageId)
{
    if (!$pageId) {
        return ['error' => 'page_id wajib diisi'];
    }

    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];

    $ch = curl_init("https://api.notion.com/v1/pages/" . $pageId);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['archived' => true]));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $result = json_decode($response, true);
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    return ['success' => true];
}

// ==== Endpoint POST — dipanggil langsung dari fetch() di pages/agenda.php ====
// Saat file ini di-require oleh pages/agenda.php (GET, buat nampilin
// halaman), blok ini dilewati — jadi aman dipakai dua-duanya.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Kasus klasik PHP: kalau ukuran TOTAL request (termasuk file) melebihi
    // post_max_size di php.ini, PHP diam-diam mengosongkan $_POST dan
    // $_FILES — tidak ada error per-file yang bisa ditangkap kode di bawah.
    // Ini caranya mendeteksi kejadian itu supaya tidak gagal secara diam-diam.
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) {
        $postMax = ini_get('post_max_size');
        $uploadMax = ini_get('upload_max_filesize');
        echo json_encode([
            'error' => "Request ditolak PHP karena kebesaran. Batas saat ini di server: post_max_size={$postMax}, upload_max_filesize={$uploadMax}. File yang kamu lampirkan kemungkinan melebihi ini — perbesar dua nilai itu di php.ini (lalu restart web server/PHP), atau coba dulu dengan file yang lebih kecil."
        ]);
        exit;
    }

    $action = $_POST['action'] ?? 'create';
    $filesInput = $_FILES['dokumen'] ?? null;

    if ($action === 'delete') {
        echo json_encode(deleteAgenda($_POST['page_id'] ?? ''));
    } elseif ($action === 'update') {
        echo json_encode(updateAgenda($_POST['page_id'] ?? '', $_POST, $filesInput));
    } else {
        echo json_encode(createAgenda($_POST, $filesInput));
    }
    exit;
}