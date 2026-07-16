<?php
require_once __DIR__.'/../config/notion.php';

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

    // Tidak menyaring/mengurutkan lewat payload Notion karena nama property Date
    // di database Agenda belum bisa dipastikan 100% — filter & sort tanggal
    // dilakukan di PHP setelah data diambil (lebih aman, tidak error kalau
    // nama property meleset).
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

        // 1. Judul Kegiatan (Title) — coba beberapa kemungkinan nama property
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

        // Kalau ada filter tanggal, lewati item yang tidak cocok
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

        // 4. PIC / Lokasi Kegiatan — coba PIC dulu, fallback ke Lokasi
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

        // 5. Status (Status property atau Select)
        $status = 'Belum';
        if (isset($props['Status']['status']['name'])) {
            $status = $props['Status']['status']['name'];
        } elseif (isset($props['Status']['select']['name'])) {
            $status = $props['Status']['select']['name'];
        }
        $cls = statusClass($status);

        // 6. Catatan / Keterangan tambahan
        $notes = '';
        foreach (['Catatan', 'Keterangan', 'Notes', 'Surat Tugas / Pengantar'] as $key) {
            if (isset($props[$key]['rich_text'][0]['plain_text'])) {
                $notes = $props[$key]['rich_text'][0]['plain_text'];
                break;
            }
        }

        $docs = getNotionFilesFromProps($props);

        $agenda[] = [
            'id'      => strtoupper(substr($item['id'], 0, 6)),
            'page_id' => $item['id'],
            'url'     => $item['url'] ?? '#',
            'title'   => $title,
            'tag'     => $tag,
            'tone'    => $tone,
            'pic'     => $pic,
            'status'  => $status,
            'cls'     => $cls,
            'notes'   => $notes,
            'date'    => $dateYmd,
            'time'    => $time,
            'dokumentasi' => $docs,
        ];
    }

    // Urutkan berdasarkan tanggal + jam (bukan cuma jam) sesuai arah $sort
    usort($agenda, function ($a, $b) {
        return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']);
    });
    if ($sort === 'desc') {
        $agenda = array_reverse($agenda);
    }

    return $agenda;
}

function getNotionFilesFromProps(array $props): array
{
    $files = [];
    foreach (['Dokumentasi', 'Dokumen', 'Surat Tugas', 'Files', 'Attachment', 'Attachments'] as $key) {
        if (!isset($props[$key])) {
            continue;
        }
        $prop = $props[$key];
        if (!isset($prop['files']) || !is_array($prop['files'])) {
            continue;
        }
        foreach ($prop['files'] as $file) {
            $url = '';
            if (isset($file['file']['url'])) {
                $url = $file['file']['url'];
            } elseif (isset($file['external']['url'])) {
                $url = $file['external']['url'];
            }
            if ($url === '') {
                continue;
            }
            $files[] = [
                'name' => $file['name'] ?? basename($url),
                'url'  => $url,
            ];
        }
        if (!empty($files)) {
            break;
        }
    }
    return $files;
}

function buildAgendaProperties(array $data): array
{
    $judul    = trim($data['kegiatan'] ?? '');
    $tanggal  = trim($data['tanggal'] ?? '');
    $waktu    = trim($data['waktu'] ?? '');
    $lokasi   = trim($data['lokasi_kegiatan'] ?? $data['pic'] ?? '');
    $kategori = trim($data['kategori'] ?? 'EVENT');
    $status   = trim($data['status'] ?? 'Belum');
    $catatan  = trim($data['catatan'] ?? '');

    $dateTimeStr = '';
    if ($tanggal !== '' && $waktu !== '') {
        $dateTimeStr = $tanggal . 'T' . $waktu . ':00+07:00';
    }

    $properties = [
        'Nama Kegiatan' => [
            'title' => [
                ['text' => ['content' => $judul]]
            ]
        ],
        'Date' => [
            'date' => $dateTimeStr !== '' ? ['start' => $dateTimeStr] : null
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
            'status' => ['name' => $status]
        ],
    ];

    if ($catatan !== '') {
        $properties['Catatan'] = [
            'rich_text' => [[
                'text' => ['content' => $catatan]
            ]]
        ];
    }

    return $properties;
}

function updateAgenda($data)
{
    $pageId = trim($data['page_id'] ?? '');
    if ($pageId === '') {
        return ['error' => 'Field page_id wajib diisi untuk update.'];
    }

    $properties = buildAgendaProperties($data);
    $payload = ['properties' => $properties];

    $ch = curl_init("https://api.notion.com/v1/pages/{$pageId}");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
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
        return ['error' => 'cURL Error: ' . $error];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($httpCode !== 200) {
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }
    return ['success' => true, 'id' => $pageId];
}

function deleteAgenda($data)
{
    $pageId = trim($data['page_id'] ?? '');
    if ($pageId === '') {
        return ['error' => 'Field page_id wajib diisi untuk hapus.'];
    }

    $url = "https://api.notion.com/v1/pages/{$pageId}";
    $headers = [
        "Authorization: Bearer " . NOTION_TOKEN,
        "Notion-Version: " . NOTION_VERSION,
        "Content-Type: application/json"
    ];
    $payload = ['archived' => true];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => 'cURL Error: ' . $error];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $result = json_decode($response, true);
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    return ['success' => true];
}

function handleAgendaRequest()
{
    $data = $_POST;
    if (empty($data) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
    }

    $action = strtolower(trim($data['action'] ?? 'create'));
    if ($action === 'update') {
        return updateAgenda($data);
    }
    if ($action === 'delete') {
        return deleteAgenda($data);
    }
    return createAgenda($data);
}

/**
 * Tentukan warna badge kategori berdasarkan kata kunci pada nama kategori.
 */
function categoryTone($cat)
{
    $c = strtolower($cat);
    if (strpos($c, 'podcast') !== false) return 'blue';
    if (strpos($c, 'live') !== false) return 'purple';
    if (strpos($c, 'produksi') !== false) return 'orange';
    if (strpos($c, 'event') !== false || strpos($c, 'kunjungan') !== false) return 'green';
    return 'gray';
}

/**
 * Tentukan kelas warna status.
 */
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
 * Buat page/agenda baru di Notion dari data form.
 *
 * PENTING: nama property di sini HARUS SAMA PERSIS dengan nama property
 * asli di database Notion Agenda kamu (beda dengan getAgenda() di atas
 * yang boleh menebak-nebak, karena untuk MENULIS Notion butuh nama yang
 * pas). Kalau ada property yang namanya beda, tinggal ganti string-nya
 * di bawah — jangan ubah bagian lain.
 *
 * @param array $data Data dari $_POST (kegiatan, tanggal, waktu, pic, kategori, status, catatan)
 * @return array
 */
function createAgenda($data)
{
    $judul    = trim($data['kegiatan'] ?? '');
    $tanggal  = trim($data['tanggal'] ?? '');
    $waktu    = trim($data['waktu'] ?? '');
    $lokasi   = trim($data['lokasi_kegiatan'] ?? $data['pic'] ?? '');
    $kategori = trim($data['kategori'] ?? 'EVENT');
    $status   = trim($data['status'] ?? 'Belum');
    $catatan  = trim($data['catatan'] ?? '');

    if ($judul === '' || $tanggal === '' || $waktu === '') {
        return ['error' => 'Judul, tanggal, dan waktu wajib diisi'];
    }

    // Gabungkan tanggal + waktu jadi ISO 8601 dengan zona waktu WIB (+07:00)
    $dateTimeStr = $tanggal . 'T' . $waktu . ':00+07:00';

    // ==== SESUAIKAN nama property di sini kalau beda dengan Notion-mu ====
    $properties = [
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
            // Ganti ke 'select' => ['name' => $status] kalau di Notion-mu
            // property Status tipenya Select biasa, bukan Status bawaan.
            'status' => ['name' => $status]
        ],
    ];

    if ($catatan !== '') {
        $properties['Catatan'] = [
            'rich_text' => [[
                'text' => ['content' => $catatan]
            ]]
        ];
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
        // Notion biasanya mengirim pesan error yang jelas di 'message'
        return ['error' => $result['message'] ?? "HTTP Error: {$httpCode}", 'raw' => $response];
    }

    return ['success' => true, 'id' => $result['id'] ?? null];
}

// ==== Jalan hanya kalau file ini diakses langsung lewat POST (dari fetch di agenda.php) ====
// Saat di-require oleh pages/agenda.php (GET), blok ini dilewati — jadi aman.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode(handleAgendaRequest());
    exit;
}