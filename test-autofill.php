<?php
// ==========================================
// 1. BACKEND: LOGIKA PENARIKAN & PARSING DATA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'parse_youtube') {
    header('Content-Type: application/json; charset=utf-8');
    
    $url = $_POST['url'] ?? '';
    $rawText = $_POST['raw_text'] ?? '';
    $description = '';

    // A. TARIK DATA DARI YOUTUBE (Diperbarui)
    if (!empty($url)) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Menyamar sebagai browser asli agar tidak diblokir YouTube
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $html = curl_exec($ch);
        curl_close($ch);

        // 1. Coba cari di Meta Tag OG (Paling aman dari blokir)
        if (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $matches) || 
            preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $matches)) {
            $description = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        }
        
        // 2. Jika Meta Tag terpotong, coba cari di JSON bawaan YouTube
        if (empty($description) || strlen($description) < 30) {
            if (preg_match('/ytInitialPlayerResponse\s*=\s*({.+?});(?:var|<\/script>)/s', $html, $matches)) {
                $json = json_decode($matches[1], true);
                if (isset($json['videoDetails']['shortDescription'])) {
                    $description = $json['videoDetails']['shortDescription'];
                }
            }
        }
    } 

    // B. JIKA GAGAL / INPUT MANUAL
    if (empty($description) && !empty($rawText)) {
        $description = $rawText;
    }

    // ==========================================
    // MESIN REGEX (Pendeteksi Pola Teks SUPER KETAT)
    // ==========================================
    $data = [
        'tema' => '',
        'penyiar' => '',
        'narasumber' => '',
        'pt' => [],
        'pa' => [],
        'deskripsi_asli' => $description
    ];

    if (!empty($description)) {
        // Hapus emoji dan karakter tersembunyi
        $cleanDesc = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $description);

        // 1. Ekstrak Pemandu
        if (preg_match('/(?:Presenter|Penyiar|Host)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
            $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
        } elseif (preg_match('/Dipandu oleh:?\s*\r?\n([^\n\r]+)/i', $cleanDesc, $m)) {
            $data['penyiar'] = trim(str_replace(['(Presenter)', '(Host)'], '', $m[1]));
        }

        // 2. Ekstrak Petugas Teknik (PT) -> Ditambahkan \b agar membaca PT sebagai kata utuh
        if (preg_match('/(?:Petugas Teknik|Petugas PT|Pengarah Teknik|\bPT\b)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
            $namaArray = explode(',', $m[1]);
            $data['pt'] = array_map('trim', $namaArray);
        }

        // 3. Ekstrak Petugas PA -> Ditambahkan \b agar tidak membaca "Pada" atau "Pagi"
        if (preg_match('/(?:Petugas PA|Pengarah Acara|\bPA\b)\s*[:\-]?\s*([^\n\r]+)/i', $cleanDesc, $m)) {
            $namaArray = explode(',', $m[1]);
            $data['pa'] = array_map('trim', $namaArray);
        }

        // 4. Ekstrak Tema
        if (preg_match('/Tema\s*[:\-]?\s*"?([^"\n\r]+)"?/i', $cleanDesc, $m)) {
            $data['tema'] = trim($m[1], " \t\n\r\0\x0B\"");
        }

        // 5. Ekstrak Narasumber
        if (preg_match('/Narasumber\s*[:\-]?\s*\r?\n?([\s\S]*?)(?=\r?\n(?:Petugas|Telepon|WhatsApp|Dapatkan|PT|PA|Pengarah|Line)|$)/i', $cleanDesc, $m)) {
            $narasumberBersih = trim($m[1]);
            $data['narasumber'] = ($narasumberBersih === ':' || $narasumberBersih === '') ? '' : ltrim($narasumberBersih, ": \t\n\r\0\x0B");
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
?>
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Auto-Fill YouTube</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="url"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        .btn { padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .autofill-box { background: #e0f2fe; border: 1px solid #bae6fd; padding: 16px; border-radius: 6px; margin-bottom: 24px; }
        .loading { display: none; color: #0284c7; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>1. Auto-Fill dari YouTube</h2>
    <div class="autofill-box">
        <div class="form-group">
            <label>Link YouTube (Opsional: Jika ingin scrape langsung)</label>
            <div style="display: flex; gap: 10px;">
                <input type="url" id="ytUrl" placeholder="https://www.youtube.com/watch?v=...">
                <button type="button" class="btn btn-primary" onclick="tarikDataYoutube()">Tarik Data URL</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>Atau Paste Manual Deskripsi (Untuk Testing Offline / Cepat)</label>
            <textarea id="ytRawText" rows="4" placeholder="Paste deskripsi YouTube di sini lalu klik tombol proses..."></textarea>
            <button type="button" class="btn btn-primary" style="margin-top: 10px;" onclick="tarikDataYoutube()">Proses Teks Manual</button>
        </div>
        <div id="loadingStatus" class="loading">Memproses data dengan Regex... Mohon tunggu...</div>
    </div>

    <h2>2. Form Hasil Ekstraksi</h2>
    <form id="addVideoForm">
        <div class="form-group">
            <label>Tema Acara</label>
            <input type="text" id="tema" name="tema" placeholder="Contoh: MPLS Ramah 2026...">
        </div>

        <div class="form-group">
            <label>Penyiar / Presenter / Host</label>
            <input type="text" id="penyiar" name="penyiar">
        </div>

        <div class="form-group">
            <label>Narasumber</label>
            <textarea id="narasumber" name="narasumber" rows="3"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Petugas Teknik (PT)</label>
                <div id="teknikContainer"></div>
                <input type="hidden" id="teknik" name="teknik">
            </div>
            <div class="form-group">
                <label>Petugas PA / Acara</label>
                <div id="paContainer"></div>
                <input type="hidden" id="pa" name="pa">
            </div>
        </div>
    </form>
</div>

<script>
// ==========================================
// DATA DUMMY (Pengganti master-data.json untuk testing)
// ==========================================
const mockPetugas = ["Dicky", "Priyo", "Anggi", "Ludfi", "Deny", "Shofirin", "Yhanuar", "Yudha", "Dayat", "Dedy", "Winsa", "Yayang", "Syukur", "Saiful", "Alif", "Selvy", "Alfian", "Firman", "Roni"];

// ==========================================
// FUNGSI TARIK DATA (AJAX ke Backend PHP)
// ==========================================
async function tarikDataYoutube() {
    const url = document.getElementById('ytUrl').value;
    const rawText = document.getElementById('ytRawText').value;
    const loading = document.getElementById('loadingStatus');
    
    if (!url && !rawText) {
        alert("Masukkan Link YouTube atau paste teks deskripsi terlebih dahulu.");
        return;
    }

    loading.style.display = 'block';

    const formData = new URLSearchParams();
    formData.append('action', 'parse_youtube');
    formData.append('url', url);
    formData.append('raw_text', rawText);

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        });

        const data = await response.json();
        
        if (data.success) {
            isiFormOtomatis(data.data);
        } else {
            alert("Gagal memproses data.");
        }
    } catch (err) {
        alert("Terjadi kesalahan jaringan.");
        console.error(err);
    } finally {
        loading.style.display = 'none';
    }
}

// ==========================================
// FUNGSI INJEKSI DATA KE FORM
// ==========================================
function isiFormOtomatis(data) {
    console.log("Hasil Ekstraksi:", data);

    // 1. Isi input teks standar
    if (data.tema) document.getElementById('tema').value = data.tema;
    if (data.penyiar) document.getElementById('penyiar').value = data.penyiar;
    if (data.narasumber) document.getElementById('narasumber').value = data.narasumber;

    // 2. Isi Hidden Input untuk PT dan PA
    if (data.pt.length > 0) document.getElementById('teknik').value = data.pt.join(', ');
    if (data.pa.length > 0) document.getElementById('pa').value = data.pa.join(', ');

    // 3. Render Ulang Custom Dropdown agar centangnya muncul
    createCustomMultiSelect('teknikContainer', 'teknik', mockPetugas);
    createCustomMultiSelect('paContainer', 'pa', mockPetugas);
}

// ==========================================
// KOMPONEN CUSTOM DROPDOWN CHECKLIST 
// ==========================================
function createCustomMultiSelect(containerId, hiddenInputId, options) {
    var container = document.getElementById(containerId);
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(s => s.trim()).filter(Boolean) : [];

    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'min-height:38px; border:1px solid #ced4da; border-radius:4px; padding:6px 30px 6px 8px; background:#fff; cursor:pointer; display:flex; flex-wrap:wrap; align-items:center; position:relative; gap:4px;';

    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu';
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:8px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:200px; overflow-y:auto; padding:4px 0; text-align:left;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    function renderChips() {
        Array.from(selectBox.children).forEach(el => { if (el !== arrow) selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Pilih petugas...';
            placeholder.style.cssText = 'color:#6c757d; font-size:14px;';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(val => {
                var chip = document.createElement('span');
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:4px; font-size:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid #cbd5e1;';
                chip.textContent = val;
                
                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:14px; font-weight:bold; color:#64748b;';
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
            label.style.cssText = 'display:flex !important; align-items:center !important; justify-content:flex-start !important; padding:6px 12px !important; cursor:pointer !important; font-size:14px !important; margin:0 !important; width:100% !important; box-sizing:border-box !important; transition:background 0.2s;';
            label.onmouseover = () => label.style.background = '#f8f9fa';
            label.onmouseout = () => label.style.background = 'transparent';

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = opt;
            cb.checked = selectedValues.includes(opt);
            cb.style.cssText = 'margin:0 10px 0 0 !important;';
            cb.onchange = e => toggleValue(opt, e.target.checked);

            var textSpan = document.createElement('span');
            textSpan.textContent = opt;

            label.appendChild(cb);
            label.appendChild(textSpan); 
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
    if (!e.target.closest('#teknikContainer') && !e.target.closest('#paContainer')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(d => d.style.display = 'none');
    }
});

// Inisialisasi awal Dropdown Kosong
createCustomMultiSelect('teknikContainer', 'teknik', mockPetugas);
createCustomMultiSelect('paContainer', 'pa', mockPetugas);
</script>
</body>
</html>