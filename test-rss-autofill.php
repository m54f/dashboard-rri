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

    // 3. Ekstrak Jam (Contoh: 15.00 - 16.00 atau 06.00 s/d Selesai)
    if (preg_match('/(\d{2}[.:]\d{2})\s*(?:s\/d|-|sd)\s*(Selesai|\d{2}[.:]\d{2})?/i', $cleanDesc, $m)) {
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
    if (preg_match('/(?:Petugas Teknik|Petugas PT|Pengarah Teknik|\bPT\b)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['pt'] = array_map('trim', explode(',', $m[1]));
    }

    // 6. PA
    if (preg_match('/(?:Petugas PA|Pengarah Acara|\bPA\b)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
        $data['pa'] = array_map('trim', explode(',', $m[1]));
    }

    // 7. Tema
    if (preg_match('/Tema\s*[:\-]?\s*"?([^"\n\r]+)"?/i', $cleanDesc, $m)) {
        $data['tema'] = trim($m[1], " \t\n\r\0\x0B\"");
    }

    // 8. Narasumber
    if (preg_match('/Narasumber\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n(?:Petugas|Telepon|WhatsApp|Dapatkan|PT|PA|Pengarah|Line)|$)/i', $cleanDesc, $m)) {
        $narasumberBersih = trim($m[1]);
        $data['narasumber'] = ($narasumberBersih === ':' || $narasumberBersih === '') ? '' : ltrim($narasumberBersih, ": \t\n\r\0\x0B");
    }

    return $data;
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
        table { width: 100%; min-width: 1500px; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 12px; vertical-align: top; }
        th { background: #f1f5f9; font-size: 13px; text-align: left; }
        
        .row-group { display: flex; flex-direction: column; gap: 8px; }
        .loading { display: none; color: #0284c7; font-weight: bold; margin-left: auto; align-self: center; }
        
        /* Dropzone Mini */
        .dropzone-mini { border: 2px dashed #cbd5e1; padding: 10px; text-align: center; border-radius: 4px; cursor: pointer; background: #f8fafc; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

<div class="container">
    <h2>Sinkronisasi Harian dari YouTube (RSS)</h2>
    
    <div class="control-panel">
        <div class="form-group" style="flex: 2; min-width: 250px;">
            <label>ID Channel YouTube</label>
            <input type="text" id="channelId" placeholder="Misal: UCPX... (Cari di link channel RRI Malang)" value="">
        </div>
        <div class="form-group" style="flex: 1; min-width: 150px;">
            <label>Pilih Tanggal</label>
            <!-- Menggunakan tanggal hari ini berdasarkan context server -->
            <input type="date" id="targetDate" value="2026-07-20">
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
// Mock data untuk dropdown petugas
const mockPetugas = ["Dicky", "Priyo", "Anggi", "Ludfi", "Deny", "Shofirin", "Yhanuar", "Yudha", "Dayat", "Dedy", "Winsa", "Yayang", "Syukur", "Saiful", "Alif", "Selvy", "Alfian", "Firman", "Roni", "Ari"];

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

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="row-group">
                    <input type="text" placeholder="Nama Program / Acara" value="${vid.title_asli.split('-')[0].trim()}" style="font-weight:bold;">
                    <input type="text" placeholder="Tema Acara" value="${vid.tema}">
                </div>
            </td>
            <td>
                <div class="row-group">
                    <select>
                        <option value="Pro 1 FM 94.6 Mhz" ${vid.programa.includes('Pro 1') ? 'selected' : ''}>Pro 1</option>
                        <option value="Pro 2 FM 87.9 Mhz" ${vid.programa.includes('Pro 2') ? 'selected' : ''}>Pro 2</option>
                        <option value="Pro 4 FM 105.3 Mhz" ${vid.programa.includes('Pro 4') ? 'selected' : ''}>Pro 4</option>
                    </select>
                    <input type="date" value="${vid.tanggal}">
                    <div style="display:flex; gap:4px;">
                        <input type="time" value="${vid.jamMulai}" title="Jam Mulai">
                        <input type="time" value="${vid.jamSelesai}" title="Jam Selesai">
                    </div>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <div style="display:flex; gap:4px;">
                        <select style="width:100px;"><option>Presenter</option><option>Penyiar</option><option>Host</option></select>
                        <input type="text" placeholder="Nama Pemandu" value="${vid.penyiar}">
                    </div>
                    <textarea rows="3" placeholder="Narasumber">${vid.narasumber}</textarea>
                </div>
            </td>
            <td>
                <div class="row-group">
                    <div style="font-size:11px; font-weight:bold; color:#666;">Teknik (PT)</div>
                    <div id="${idPT}"></div>
                    <input type="hidden" id="${hidPT}" value="${vid.pt.join(', ')}">
                    
                    <div style="font-size:11px; font-weight:bold; color:#666; margin-top:8px;">PA / Acara</div>
                    <div id="${idPA}"></div>
                    <input type="hidden" id="${hidPA}" value="${vid.pa.join(', ')}">
                </div>
            </td>
            <td>
                <div class="row-group">
                    <input type="url" value="${vid.url}" placeholder="URL YouTube">
                    <div class="dropzone-mini">Pilih / Tarik Flyer</div>
                </div>
            </td>
            <td style="vertical-align:middle; text-align:center;">
                <button class="btn btn-success" onclick="alert('Ini simulasi. Tombol ini nantinya mengirim data baris ini ke database Notion.')">Simpan</button>
            </td>
        `;
        tbody.appendChild(tr);

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