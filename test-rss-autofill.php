<?php
// ==========================================
// 1. BACKEND: LOGIKA PENARIKAN RSS & PARSING
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_rss') {
    header('Content-Type: application/json; charset=utf-8');
    
    $channelId = $_POST['channel_id'] ?? '';
    $targetDate = $_POST['target_date'] ?? date('Y-m-d');
    
    if (empty($channelId)) {
        echo json_encode(['success' => false, 'error' => 'ID Channel tidak boleh kosong.']);
        exit;
    }

    $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id=" . urlencode($channelId);
    
    $ch = curl_init($rssUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $xmlContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($xmlContent)) {
        echo json_encode(['success' => false, 'error' => 'Gagal mengambil RSS feed. Pastikan Channel ID benar.']);
        exit;
    }

    $xml = @simplexml_load_string($xmlContent);
    if (!$xml) {
        echo json_encode(['success' => false, 'error' => 'Format XML tidak valid.']);
        exit;
    }

    $namespaces = $xml->getNamespaces(true);
    $videos = [];

    // Looping setiap video dari RSS
    foreach ($xml->entry as $entry) {
        $media = $entry->children($namespaces['media']);
        $title = (string)$entry->title;
        $url = (string)$entry->link['href'];
        $description = (string)$media->group->description;
        
        // --- TAMBAHAN KODE: Ambil Thumbnail / Flyer dari RSS ---
        $thumbnailUrl = '';
        if (isset($media->group->thumbnail)) {
            $attrs = $media->group->thumbnail->attributes();
            $thumbnailUrl = (string)$attrs['url'];
        }
        
        // 1. Ekstrak data dari deskripsi (termasuk mencari tanggal asli yang tertulis)
        $parsed = parseDeskripsi($description);
        
        // 2. Ambil waktu sistem YouTube sebagai cadangan (dikonversi ke WIB)
        $dt = new DateTime((string)$entry->published);
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        $fallbackDate = $dt->format('Y-m-d');

        // 3. PRIORITAS: Gunakan tanggal dari deskripsi jika ada, jika tidak pakai cadangan
        $realDate = !empty($parsed['tanggal_asli']) ? $parsed['tanggal_asli'] : $fallbackDate;

        // 4. Filter berdasarkan tanggal yang benar-benar tayang
        if ($realDate === $targetDate) {
            $parsed['title_asli'] = $title;
            $parsed['url'] = $url;
            $parsed['tanggal'] = $realDate;
            
            // --- TAMBAHAN KODE: Simpan url thumbnail ke data yang dikirim ---
            $parsed['flyer'] = $thumbnailUrl; 
            
            $videos[] = $parsed;
        }
    }

    echo json_encode(['success' => true, 'data' => $videos]);
    exit;
}

// Fungsi Mesin Regex
function parseDeskripsi($description) {
    $data = [
        'tema' => '', 'penyiar' => '', 'narasumber' => '',
        'pt' => [], 'pa' => [], 'programa' => '', 'jamMulai' => '', 'jamSelesai' => '',
        'tanggal_asli' => '' // Tempat menyimpan tanggal asli dari teks
    ];

    if (empty($description)) return $data;

    $cleanDesc = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $description);

    // 1. Ekstrak Tanggal dari Teks (Misal: "Selasa, 14 Juli 2026" atau "15 Juli 2026")
    $bulanIndo = [
        'januari'=>'01', 'februari'=>'02', 'maret'=>'03', 'april'=>'04', 
        'mei'=>'05', 'juni'=>'06', 'juli'=>'07', 'agustus'=>'08', 
        'september'=>'09', 'oktober'=>'10', 'november'=>'11', 'desember'=>'12'
    ];
    
    if (preg_match('/(?:\b(?:Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu)\b\s*,?\s*)?(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})/i', $cleanDesc, $m)) {
        $tgl = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $bln = $bulanIndo[strtolower($m[2])] ?? '01';
        $thn = $m[3];
        $data['tanggal_asli'] = "$thn-$bln-$tgl"; // Konversi ke format YYYY-MM-DD
    }

    // 2. Ekstrak Programa
    if (preg_match('/Pro\s*1/i', $cleanDesc)) $data['programa'] = 'Pro 1 FM 94.6 Mhz';
    elseif (preg_match('/Pro\s*2/i', $cleanDesc)) $data['programa'] = 'Pro 2 FM 87.9 Mhz';
    elseif (preg_match('/Pro\s*4/i', $cleanDesc)) $data['programa'] = 'Pro 4 FM 105.3 Mhz';

    // 3. Ekstrak Jam (Contoh: 15.00 - 16.00, 15.00 – 16.00, atau 06.00 s/d Selesai)
    if (preg_match('/(\d{2}[.:]\d{2})\s*(?:s\/d|-|–|—|sd)\s*(Selesai|\d{2}[.:]\d{2})?/i', $cleanDesc, $m)) {
        $data['jamMulai'] = str_replace('.', ':', $m[1]);
        if (isset($m[2]) && strtolower($m[2]) !== 'selesai') {
            $data['jamSelesai'] = str_replace('.', ':', $m[2]);
        }
    }

    // 4. Pemandu
    if (preg_match('/(?:Presenter|Penyiar|Host)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
    } elseif (preg_match('/Dipandu oleh:?\s*\r?\n([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
    }

    // 5. PT
    if (preg_match('/(?:Petugas Teknik|Petugas PT|Pengarah Teknik)\s*[:\-]?\s*([^\n\r]+)|\bPT\b\s*[:\-]\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        // Ambil nama dari grup regex pertama atau kedua yang berhasil cocok
        $ptString = !empty($m[1]) ? $m[1] : (isset($m[2]) ? $m[2] : '');
        $data['pt'] = mapPetugasToMaster($ptString);
    }

    // 6. PA
    if (preg_match('/(?:Petugas PA|Pengarah Acara|\bPA\b)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['pa'] = mapPetugasToMaster($m[1]);
    }

    // 7. Tema
    if (preg_match('/Tema\s*[:\-]?\s*"?([^"\n\r]+)"?/i', $cleanDesc, $m)) {
        // Jika ada tulisan eksplisit "Tema : "
        $data['tema'] = trim($m[1], " \t\n\r\0\x0B\"");
    } else {
        // Fallback: Ambil baris kedua dari deskripsi jika kata "Tema" tidak ditulis
        $lines = preg_split('/\r\n|\r|\n/', $cleanDesc);
        // Bersihkan baris-baris yang kosong
        $nonEmptyLines = array_values(array_filter(array_map('trim', $lines)));
        
        if (count($nonEmptyLines) >= 2) {
            $potensiTema = $nonEmptyLines[1];
            
            // Pastikan baris kedua ini bukan baris tanggal atau jam tayang
            if (!preg_match('/(?:Senin|Selasa|Rabu|Kamis|Jum\'?at|Sabtu|Minggu|Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|\d{2}[.:]\d{2})/i', $potensiTema)) {
                // Hapus tanda kutip " atau ' jika ada di awal/akhir tema
                $data['tema'] = trim($potensiTema, " \t\n\r\0\x0B\"'”’“‘");
            }
        }
    }

    // 8. Narasumber
    // Ditambahkan \s* setelah \n agar spasi sisa penghapusan emoji tidak menggagalkan batas berhenti
    if (preg_match('/(?:Narasumber|Bersama)\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n\s*(?:Dipandu|Presenter|Penyiar|Host|Petugas|Telepon|WhatsApp|Dapatkan|PT|PA|Pengarah|Line|Pro\s*[124])|$)/i', $cleanDesc, $m)) {
        $rawNarasumber = ltrim(trim($m[1]), ": \t\n\r\0\x0B");
        
        // Membersihkan icon, emoji, bullet point (- / * / •), dan penomoran (1. 2.) di setiap baris
        $lines = explode("\n", $rawNarasumber);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Regex ini menghapus semua simbol (\p{S}), tanda baca (\p{P}), dan angka (\d) di awal kalimat
            $line = preg_replace('/^[\p{So}\p{S}\p{P}\d]+\s*/u', '', $line);
            
            if (!empty($line)) {
                $cleanLines[] = $line;
            }
        }
        
        $data['narasumber'] = implode("\n", $cleanLines);
    }

    return $data;
}

// Fungsi untuk mencocokkan nama dengan master data (Fuzzy Matching)
function mapPetugasToMaster($rawString) {
    // Array master data petugas
    $masterPetugas = [
        "Dicky", "Priyo", "Anggi", "Ludfi", "Deny", "Shofirin", 
        "Yhanuar", "Yudha", "Dayat", "Dedy", "Winsa", "Yayank", 
        "Syukur", "Saiful", "Alif", "Selvy", "Alfian"
    ];
    
    $results = [];
    // Pisahkan nama berdasarkan koma, karakter '&', atau kata 'dan'
    $rawNames = array_map('trim', preg_split('/[,&]|\bdan\b/i', $rawString));
    
    foreach ($rawNames as $name) {
        if (empty($name)) continue;
        
        $bestMatch = ucwords(strtolower($name)); // Default: gunakan teks asli yang dirapikan
        $highestSim = 0;
        $nameLower = strtolower($name);
        
        foreach ($masterPetugas as $master) {
            $masterLower = strtolower($master);
            
            // 1. Cek apakah ada kecocokan bagian teks (contoh: "Yhanuar M." langsung jadi "Yhanuar")
            if (strpos($nameLower, $masterLower) !== false) {
                $bestMatch = $master;
                break;
            }
            
            // 2. Cek kemiripan string (Fuzzy match) untuk mengatasi salah ketik
            similar_text($nameLower, $masterLower, $percent);
            if ($percent > $highestSim) {
                $highestSim = $percent;
                // Jika tingkat kemiripan minimal 65%, anggap itu nama dari master data
                if ($percent >= 65) {
                    $bestMatch = $master;
                }
            }
        }
        $results[] = $bestMatch;
    }
    return $results;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Fill RSS YouTube Harian</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
        .container { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow-x: auto; }
        h2 { margin-top: 0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        .control-panel { background: #e0f2fe; border: 1px solid #bae6fd; padding: 16px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; }
        label { font-weight: 600; margin-bottom: 6px; font-size: 13px; }
        input[type="text"], input[type="url"], input[type="date"], input[type="time"], textarea, select { padding: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; width: 100%; box-sizing: border-box; }
        .btn { padding: 9px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: #fff; }
        
        /* Tabel Layout */
        table { width: 100%; min-width: 1200px;border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; vertical-align: top; }
        th { background: #f1f5f9; font-size: 13px; text-align: left; }
        
        .row-group { display: flex; flex-direction: column; gap: 8px; }
        .loading { display: none; color: #0284c7; font-weight: bold; margin-left: auto; align-self: center; }
        
        /* Dropzone Mini */
        .dropzone-mini { border: 2px dashed #cbd5e1; padding: 10px; text-align: center; border-radius: 4px; cursor: pointer; background: #f8fafc; font-size: 12px; color: #64748b; }
        /* Label Input Tabel */
        .tabel-label { 
            font-size: 11px; 
            font-weight: 600; 
            color: #64748b; 
            margin-bottom: -2px; /* Mendekatkan label ke input */
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Sinkronisasi Harian dari YouTube (RSS)</h2>
    
    <div class="control-panel">
        <div class="form-group" style="flex: 2; min-width: 250px;">
            <label>ID Channel YouTube</label>
            <input type="text" id="channelId" placeholder="Misal: UCPX... (Cari di link channel RRI Malang)" value="UCa45qWSDXbdgLBjrGfQ7tmA">
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Pilih Tanggal</label>
            <!-- Menggunakan tanggal hari ini berdasarkan context server -->
            <input type="date" id="targetDate" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <button class="btn btn-primary" onclick="tarikRss()">Tarik Data Video</button>
        </div>
        <div id="loadingStatus" class="loading">Menghubungi YouTube & Mengekstrak Data...</div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="20%">Acara & Tema</th>
                <th width="15%">Jadwal & Programa</th>
                <th width="20%">Pemandu & Narasumber</th>
                <th width="20%">Petugas (PT & PA)</th>
                <th width="15%">Media (URL & Flyer)</th>
                <th width="10%">Aksi</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <tr><td colspan="6" style="text-align:center; color:#999; padding:30px;">Belum ada data ditarik. Masukkan ID Channel dan klik Tarik Data.</td></tr>
        </tbody>
    </table>
</div>

<script>
// --- TAMBAHAN KODE: Mengambil data acara dan pemetaan programanya ---
const acaraToProgramaMap = <?php 
    $masterDataPath = __DIR__ . '/data/master-data.json'; 
    $mapAcaraProg = [];
    
    // Pemetaan nama agar sesuai dengan value dropdown Programa di tabel
    $programaFullNames = [
        'Pro 1' => 'Pro 1 FM 94.6 Mhz',
        'Pro 2' => 'Pro 2 FM 87.9 Mhz',
        'Pro 4' => 'Pro 4 FM 105.3 Mhz'
    ];
    
    if (file_exists($masterDataPath)) {
        $masterData = json_decode(file_get_contents($masterDataPath), true);
        if (isset($masterData['acara']) && is_array($masterData['acara'])) {
            foreach ($masterData['acara'] as $prog => $listAcara) {
                $fullProgValue = isset($programaFullNames[$prog]) ? $programaFullNames[$prog] : $prog;
                foreach ($listAcara as $acara) {
                    $mapAcaraProg[$acara] = $fullProgValue;
                }
            }
        }
    }
    echo json_encode($mapAcaraProg);
?>;

// Mengambil daftar nama acara saja (Keys dari map di atas)
const masterAcaraList = Object.keys(acaraToProgramaMap);

// Fungsi untuk mengganti programa otomatis saat dropdown acara diubah
function updatePrograma(index, selectedAcara) {
    const progSelect = document.getElementById(`programa_${index}`);
    if (progSelect && acaraToProgramaMap[selectedAcara]) {
        progSelect.value = acaraToProgramaMap[selectedAcara];
    }
}

// ==========================================
// FUNGSI DINAMIS NARASUMBER
// ==========================================
function addNarasumberField(index, value = '') {
    const container = document.getElementById(`narasumber_container_${index}`);
    if (!container) return;
    
    // Wrapper (Bungkus) untuk 1 baris input + tombol hapus
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'display:flex; gap:4px; align-items:center;';
    
    // Input teks
    const input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Nama Narasumber & Jabatan';
    input.value = value;
    input.style.cssText = 'flex:1; padding:6px 8px; border:1px solid #ced4da; border-radius:4px; font-size:12px;';
    
    // Tombol Hapus (Silang)
    const btnRemove = document.createElement('button');
    btnRemove.type = 'button';
    btnRemove.innerHTML = '&times;';
    btnRemove.title = "Hapus baris ini";
    btnRemove.style.cssText = 'background:#ef4444; color:white; border:none; border-radius:4px; padding:0 8px; height:28px; cursor:pointer; font-weight:bold; font-size:14px;';
    
    // Logika ketika tombol hapus diklik
    btnRemove.onclick = function() {
        container.removeChild(wrapper);
    };
    
    wrapper.appendChild(input);
    wrapper.appendChild(btnRemove);
    container.appendChild(wrapper);
}

// Mock data untuk dropdown petugas
// Data untuk dropdown petugas (disesuaikan dengan master-data.php)
const mockPetugas = [
    "Dicky", "Priyo", "Anggi", "Ludfi", "Deny", "Shofirin", 
    "Yhanuar", "Yudha", "Dayat", "Dedy", "Winsa", "Yayank", 
    "Syukur", "Saiful", "Alif", "Selvy", "Alfian"
];

async function tarikRss() {
    const channelId = document.getElementById('channelId').value.trim();
    const targetDate = document.getElementById('targetDate').value;
    
    if (!channelId) { alert("ID Channel wajib diisi!"); return; }

    document.getElementById('loadingStatus').style.display = 'block';
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Memproses RSS...</td></tr>';

    const formData = new URLSearchParams();
    formData.append('action', 'fetch_rss');
    formData.append('channel_id', channelId);
    formData.append('target_date', targetDate);

    try {
        const res = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });
        const data = await res.json();
        
        if (data.success) {
            renderTabel(data.data);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" style="color:red; text-align:center;">Error: ${data.error}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" style="color:red; text-align:center;">Terjadi kesalahan jaringan.</td></tr>`;
    } finally {
        document.getElementById('loadingStatus').style.display = 'none';
    }
}

function renderTabel(videos) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (videos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#666;">Tidak ada video ditemukan untuk tanggal tersebut.</td></tr>';
        return;
    }

    videos.forEach((vid, index) => {
        // Tentukan ID unik per baris
        const idPT = `pt_${index}`;
        const idPA = `pa_${index}`;
        const hidPT = `hidden_pt_${index}`;
        const hidPA = `hidden_pa_${index}`;

        // --- TAMBAHAN KODE: Render opsi dropdown Nama Acara dengan Normalisasi ---
        const extractedTitle = vid.title_asli.split('-')[0].trim();
        
        // Fungsi untuk membersihkan teks agar pencocokan lebih akurat
        // Fungsi untuk membersihkan teks agar pencocokan lebih akurat
        const normalizeText = (text) => {
            return text.toLowerCase()
                       .replace(/&/g, 'dan')
                       .replace(/berbicara/g, 'bicara') // Alias: menyamakan 'berbicara' dengan 'bicara'
                       .replace(/talkshow/g, 'dialog')  // Alias tambahan: menyamakan 'talkshow' dengan 'dialog' (opsional)
                       .replace(/[^a-z0-9]/g, ''); // Hapus karakter selain huruf dan angka
        };

        const normalizedTitle = normalizeText(extractedTitle);
        let optionsAcara = '<option value="">-- Pilih Acara --</option>';
        
        masterAcaraList.forEach(acara => {
            const normalizedAcara = normalizeText(acara);
            
            // Pencocokan: Cek apakah setelah dibersihkan teksnya saling mengandung
            let isSelected = '';
            if (normalizedTitle !== '' && normalizedAcara !== '') {
                if (normalizedTitle.includes(normalizedAcara) || normalizedAcara.includes(normalizedTitle)) {
                    isSelected = 'selected';
                }
            }
            
            optionsAcara += `<option value="${acara}" ${isSelected}>${acara}</option>`;
        });

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="row-group">
                    <label class="tabel-label">Nama Acara</label>
                    <select id="acara_${index}" onchange="updatePrograma(${index}, this.value)" style="font-weight:bold; padding:8px; border:1px solid #ced4da; border-radius:4px; font-size:13px; width:100%; box-sizing:border-box;">
                        ${optionsAcara}
                    </select>
                    
                    <label class="tabel-label" style="margin-top:6px;">Tema</label>
                    <textarea rows="3" placeholder="Tema Acara" style="resize: none;">${vid.tema}</textarea>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Programa</label>
                    <select id="programa_${index}">
                        <option value="Pro 1 FM 94.6 Mhz" ${vid.programa.includes('Pro 1') ? 'selected' : ''}>Pro 1</option>
                        <option value="Pro 2 FM 87.9 Mhz" ${vid.programa.includes('Pro 2') ? 'selected' : ''}>Pro 2</option>
                        <option value="Pro 4 FM 105.3 Mhz" ${vid.programa.includes('Pro 4') ? 'selected' : ''}>Pro 4</option>
                    </select>
                    
                    <label class="tabel-label" style="margin-top:6px;">Tanggal Tayang</label>
                    <input type="date" value="${vid.tanggal}">
                    
                    <div style="display:flex; gap:8px; margin-top:6px;">
                        <div style="flex:1;">
                            <label class="tabel-label">Mulai</label>
                            <input type="time" value="${vid.jamMulai}" title="Jam Mulai">
                        </div>
                        <div style="flex:1;">
                            <label class="tabel-label">Selesai</label>
                            <input type="time" value="${vid.jamSelesai}" title="Jam Selesai">
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Pemandu / Host / Penyiar</label>
                    <div style="display:flex; gap:4px;">
                        <input type="text" placeholder="Nama Pemandu" value="${vid.penyiar}">
                    </div>
                    
                    <label class="tabel-label" style="margin-top:6px;">Narasumber</label>
                    <!-- Wadah untuk list narasumber dinamis -->
                    <div id="narasumber_container_${index}" style="display:flex; flex-direction:column; gap:4px;"></div>
                    <!-- Tombol Tambah Narasumber -->
                    <button type="button" onclick="addNarasumberField(${index})" style="margin-top:4px; font-size:11px; padding:4px 8px; cursor:pointer; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; color:#334155; font-weight:600;">+ Tambah Narasumber</button>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Teknik (PT)</label>
                    <div id="${idPT}"></div>
                    <input type="hidden" id="${hidPT}" value="${vid.pt.join(', ')}">
                    
                    <label class="tabel-label" style="margin-top:6px;">PA / Acara</label>
                    <div id="${idPA}"></div>
                    <input type="hidden" id="${hidPA}" value="${vid.pa.join(', ')}">
                </div>
            </td>
            <td>
                <div class="row-group">
                    <label class="tabel-label">Link YouTube</label>
                    <input type="url" value="${vid.url}" placeholder="URL YouTube">
                    
                    <label class="tabel-label" style="margin-top:6px;">Flyer / Thumbnail</label>
                    <div class="dropzone-mini" style="padding: 0; overflow: hidden; position: relative; min-height: 80px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 2px dashed #cbd5e1;">
                        ${vid.flyer 
                            ? `<img src="${vid.flyer}" style="width: 100%; height: 100%; object-fit: cover; position: absolute;" alt="Flyer">` 
                            : `<span style="padding:10px;">Pilih / Tarik Flyer</span>`
                        }
                    </div>
                    <input type="hidden" class="hidden-flyer-url" value="${vid.flyer || ''}">
                </div>
            </td>
            <td style="vertical-align:middle; text-align:center;">
                <button class="btn btn-success" onclick="alert('Ini simulasi. Tombol ini nantinya mengirim data baris ini ke database Notion.')">Simpan</button>
            </td>
        `;
        tbody.appendChild(tr);

        // --- TAMBAHAN KODE: Render input narasumber dinamis ---
        const narasumberList = vid.narasumber ? vid.narasumber.split('\n') : [''];
        narasumberList.forEach(nama => {
            if (nama.trim() !== '') {
                addNarasumberField(index, nama.trim());
            }
        });
        // Jika kosong sama sekali, tampilkan minimal 1 input kosong
        const narasumberContainer = document.getElementById(`narasumber_container_${index}`);
        if (narasumberContainer && narasumberContainer.children.length === 0) {
            addNarasumberField(index, '');
        }

        // Render Custom Dropdown untuk tiap baris setelah elemen HTML dimuat
        createCustomMultiSelect(idPT, hidPT, mockPetugas);
        createCustomMultiSelect(idPA, hidPA, mockPetugas);
    });
}

// ==========================================
// CUSTOM DROPDOWN MULTISELECT (Versi Tabel)
// ==========================================
function createCustomMultiSelect(containerId, hiddenInputId, options) {
    var container = document.getElementById(containerId);
    if(!container) return;
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];

    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'min-height:32px; border:1px solid #ced4da; border-radius:4px; padding:4px 24px 4px 6px; background:#fff; cursor:pointer; display:flex; flex-wrap:wrap; align-items:center; position:relative; gap:4px; font-size:12px;';

    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu';
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; width:220px; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:6px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:150px; overflow-y:auto; padding:4px 0; text-align:left;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    function renderChips() {
        Array.from(selectBox.children).forEach(el => { if (el !== arrow) selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Pilih...';
            placeholder.style.color = '#999';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(val => {
                var chip = document.createElement('span');
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 6px; border-radius:4px; font-size:11px; display:inline-flex; align-items:center; gap:4px; border:1px solid #cbd5e1;';
                chip.textContent = val;
                
                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:12px; font-weight:bold; color:#64748b;';
                closeBtn.onclick = e => { e.stopPropagation(); toggleValue(val, false); };
                chip.appendChild(closeBtn);
                selectBox.insertBefore(chip, arrow);
            });
        }
        hiddenInput.value = selectedValues.join(', ');
    }

    function toggleValue(val, isChecked) {
        if (isChecked && !selectedValues.includes(val)) selectedValues.push(val);
        else if (!isChecked) selectedValues = selectedValues.filter(v => v !== val);
        renderChips();
        renderList(searchInput.value);
    }

    function renderList(filterText) {
        listDiv.innerHTML = '';
        var filtered = options.filter(o => o.toLowerCase().indexOf((filterText||'').toLowerCase()) !== -1);
        filtered.forEach(opt => {
            var label = document.createElement('label');
            label.style.cssText = 'display:flex !important; align-items:center !important; justify-content:flex-start !important; padding:4px 8px !important; cursor:pointer !important; font-size:12px !important; margin:0 !important; width:100% !important; box-sizing:border-box !important; transition:background 0.2s;';
            label.onmouseover = () => label.style.background = '#f8f9fa';
            label.onmouseout = () => label.style.background = 'transparent';

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = opt;
            cb.checked = selectedValues.includes(opt);
            cb.style.cssText = 'margin:0 8px 0 0 !important;';
            cb.onchange = e => toggleValue(opt, e.target.checked);

            label.appendChild(cb);
            label.appendChild(document.createTextNode(opt)); 
            listDiv.appendChild(label);
        });
    }

    selectBox.onclick = () => {
        var isOpen = dropdown.style.display === 'block';
        document.querySelectorAll('.custom-dropdown-menu').forEach(d => d.style.display = 'none');
        if (!isOpen) { dropdown.style.display = 'block'; searchInput.focus(); renderList(searchInput.value); }
    };
    searchInput.oninput = e => renderList(e.target.value);
    renderChips();
    renderList('');
}

document.addEventListener('click', e => {
    if (!e.target.closest('[id^="pt_"]') && !e.target.closest('[id^="pa_"]')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(d => d.style.display = 'none');
    }
});
</script>
</body>
</html>