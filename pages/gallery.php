<?php
require_once __DIR__ . '/../api/youtube.php';

$base = '..'; $pageTitle = 'Rekap Youtube'; $activeMenu = 'gallery';

$videos = getYoutubeVideos();
$errorMessage = null;

if (!is_array($videos)) {
    $errorMessage = 'Gagal memuat data dari Notion.';
    $videos = [];
} elseif (isset($videos['error'])) {
    $errorMessage = $videos['error'];
    $videos = [];
}

include __DIR__ . '/../includes/header.php';
?>
<div id="galleryMainView">
<div class="page-head">
  <div>
    <h2>Rekap Youtube</h2>
    <p>Koleksi siaran yang telah tayang.</p>
  </div>
  <button class="btn btn-primary" onclick="showAddView()"><i class="ri-add-line"></i> Tambah Video</button>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div class="card-title"><i class="ri-play-list-2-line"></i> Daftar Video</div>
    
    <div class="table-filters" style="display:flex; gap:10px;">
        <select id="filterPrograma" onchange="filterTable()" style="padding:5px; border-radius:4px;">
            <option value="">Semua Programa</option>
            <option value="Pro 1">Pro 1</option>
            <option value="Pro 2">Pro 2</option>
            <option value="Pro 4">Pro 4</option>
        </select>
        <!-- Filter Petugas Menggunakan Select -->
        <select id="filterPetugas" onchange="filterTable()" style="padding:5px; border-radius:4px;">
            <option value="">Semua Petugas</option>
            <!-- Opsi akan diisi otomatis oleh JS -->
        </select>
    </div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="videoTable">
      <thead>
        <tr>
          <th>Thumbnail</th>
          <th>Judul Video</th>
          <th>Program</th>
          <th>Tanggal</th>
          <th>Jam</th>
          <th>Penyiar</th>
          <th>Narasumber</th>
          <th>Petugas PT</th>
          <th>Petugas PA</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($videos)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Data video gallery belum tersedia.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($videos as $video): ?>
            <?php
              $title = isset($video['title']) && $video['title'] !== '' ? $video['title'] : 'Tanpa Judul';
              $namaAcara = isset($video['namaAcara']) && $video['namaAcara'] !== '' ? $video['namaAcara'] : '';
              $tema = isset($video['tema']) ? $video['tema'] : '';
              $programa = isset($video['programa']) && $video['programa'] !== '' ? $video['programa'] : 'Umum';
              $programaColor = isset($video['programaColor']) ? $video['programaColor'] : 'gray';
              $date = isset($video['date']) && $video['date'] !== '' ? $video['date'] : 'TBA';
              $time = isset($video['time']) && $video['time'] !== '' ? $video['time'] : 'TBA';
              $url = isset($video['url']) && $video['url'] !== '' ? $video['url'] : '';
              $flyer = isset($video['flyer']) && $video['flyer'] !== '' ? $video['flyer'] : '';
              $penyiar = isset($video['penyiar']) && $video['penyiar'] !== '' ? $video['penyiar'] : '';
              $narasumber = isset($video['narasumber']) && is_array($video['narasumber']) ? $video['narasumber'] : [];
              $teknik = isset($video['teknik']) ? $video['teknik'] : '';
              $pa = isset($video['pa']) ? $video['pa'] : '';

              // Map Notion colors to theme palette
              $colorMap = [
                  'gray'    => '#64748b',  // --secondary
                  'brown'   => '#92400e',  // warm brown
                  'orange'  => '#f59e0b',  // --warning
                  'yellow'  => '#fbbf24',  // amber
                  'green'   => '#10b981',  // --success
                  'blue'    => '#2563eb',  // --primary
                  'purple'  => '#8b5cf6',  // --purple
                  'pink'    => '#ec4899',  // rose-500
                  'red'     => '#ef4444',  // --danger
                  'default' => '#64748b'   // --secondary
              ];
              $bgColor = isset($colorMap[$programaColor]) ? $colorMap[$programaColor] : $colorMap['default'];
            ?>
            <?php $videoData = htmlspecialchars(json_encode([
              'id' => $video['id'],
              'namaAcara' => $namaAcara,
              'title' => $title,
              'tema' => $tema,
              'programa' => $programa,
              'programaColor' => $programaColor,
              'flyer' => $flyer,
              'date' => $date,
              'time' => $time,
              'url' => $url,
              'penyiar' => $penyiar,
              'narasumber' => $narasumber,
              'teknik' => $teknik,
              'pa' => $pa
            ]), ENT_QUOTES, 'UTF-8'); ?>
            <tr class="video-row" data-video="<?= $videoData ?>">
              <td>
                <?php if ($flyer !== ''): ?>
                  <img src="<?= htmlspecialchars($flyer, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" class="video-thumb">
                <?php else: ?>
                  <div class="video-thumb video-thumb-empty"><i class="ri-image-line"></i></div>
                <?php endif; ?>
              </td>
              <td class="cell-title">
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($namaAcara !== ''): ?>
                  <div class="cell-sub"><?= htmlspecialchars($namaAcara, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="tag" style="background:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>1a; color:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; border:1px solid <?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>4d;">
                  <?= htmlspecialchars($programa, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </td>
              <td class="cell-sub"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="cell-sub"><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></td>
              <td class="cell-sub"><?= $penyiar !== '' ? htmlspecialchars($penyiar, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-sub"><?= !empty($narasumber) ? htmlspecialchars(implode(', ', $narasumber), ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-sub"><?= $teknik !== '' ? htmlspecialchars($teknik, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-sub"><?= $pa !== '' ? htmlspecialchars($pa, ENT_QUOTES, 'UTF-8') : '—' ?></td>
              <td class="cell-actions">
                <?php if ($url !== ''): ?>
                <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="icon-action view" title="Buka di Youtube" onclick="event.stopPropagation();">
                  <i class="ri-external-link-line"></i>
                </a>
                <?php endif; ?>
                <button type="button" class="icon-action edit" title="Edit" onclick="event.stopPropagation(); openEditView(JSON.parse(this.closest('tr').dataset.video));">
                  <i class="ri-edit-line"></i>
                </button>
                <button type="button" class="icon-action delete" title="Hapus" onclick="event.stopPropagation(); deleteVideoRow('<?= htmlspecialchars($video['id'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($title)) ?>');">
                  <i class="ri-delete-bin-line"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .video-thumb { width:64px; height:40px; object-fit:cover; border-radius:6px; display:block; }
  .video-thumb-empty { display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#999; font-size:1.1rem; }
  .video-row:hover { background:rgba(0,0,0,0.03); }
  .icon-action.view { color:#0ea5e9; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; text-decoration:none; display:inline-flex; }
  .icon-action.view:hover { background:rgba(14,165,233,0.12); }
</style>
</div>

<div id="addVideoView" style="display:none;">
  <div class="page-head">
    <div>
      <button class="btn btn-secondary" onclick="hideAddView()"><i class="ri-arrow-left-line"></i> Kembali</button>
    </div>
  </div>
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">
    <div>
    <h2 id="addVideoTitle" style="margin-bottom:4px;">Tambah Video</h2>
    <p style="color:var(--text-secondary); margin-bottom:24px;">Isi detail video yang akan ditambahkan ke rekap.</p>

    <form id="addVideoForm" onsubmit="event.preventDefault(); submitAddVideo();">
      <input type="hidden" id="judulVideo" name="judul">
      <input type="hidden" id="namaAcaraDisplayHidden" name="namaAcaraDisplay">

      <div class="form-group">
        <label>Live Dimana (Programa)</label>
        <select id="live" name="live" onchange="handleLiveChange(); generateDescription();" required>
          <option value="94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official">Pro 1 FM 94.6 Mhz</option>
          <option value="87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official">Pro 2 FM 87.9 Mhz</option>
          <option value="105.3 FM Pro 4 RRI Malang dan Youtube RRI Malang Official">Pro 4 FM 105.3 Mhz</option>
        </select>
      </div>

      <div class="form-group">
        <label>Nama Program / Acara</label>
        <div id="programContainer" class="form-row" style="grid-template-columns:1fr;">
          <select id="program" name="program" onchange="generateDescription()" required>
            <option value="">— Pilih Nama Acara —</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Tema Acara</label>
        <input type="text" id="tema" name="tema" placeholder="Contoh: MPLS Ramah 2026, Tanpa Perploncoan" oninput="generateDescription()">
      </div>

      <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="form-group">
          <label>Tanggal</label>
          <input type="date" id="tanggal" name="tanggal" required oninput="generateDescription()">
        </div>
        <div class="form-group"><label>Jam Mulai</label><input type="time" id="jamMulai" name="jamMulai" required oninput="generateDescription()"></div>
        <div class="form-group"><label>Jam Selesai</label><input type="time" id="jamSelesai" name="jamSelesai" oninput="generateDescription()"></div>
      </div>

      <div class="form-group">
        <label>Pemandu Acara</label>
        <div class="form-row" style="grid-template-columns:120px 1fr;">
          <select id="jenisPenyiar" name="jenisPenyiar" onchange="generateDescription()">
            <option value="Presenter">Presenter</option>
            <option value="Penyiar">Penyiar</option>
            <option value="Host">Host</option>
          </select>
          <select id="penyiar" name="penyiar" onchange="generateDescription()">
            <option value="">— Pilih Nama Pemandu —</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Narasumber</label>
        <textarea id="narasumber" name="narasumber" rows="2" placeholder="1. Nama (Jabatan)&#10;2. Nama (Jabatan)" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:4px; font-size:14px;" oninput="generateDescription()"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Petugas Teknik (PT)</label>
          <!-- Container untuk custom dropdown -->
          <div id="teknikContainer"></div>
          <input type="hidden" id="teknik" name="teknik">
        </div>
        <div class="form-group">
          <label>Petugas PA / Acara</label>
          <!-- Container untuk custom dropdown -->
          <div id="paContainer"></div>
          <input type="hidden" id="pa" name="pa">
        </div>
      </div>

      <div class="form-group">
        <label>Link Youtube</label>
        <input type="url" name="link" placeholder="https://youtube.com/...">
      </div>

      <div class="form-group">
        <label>Flyer (Gambar) <span style="color:var(--text-secondary); font-weight:400; font-size:0.85rem;">— Gunakan Dropzone</span></label>
        <div class="dropzone" id="dropzone" onclick="document.getElementById('field_flyer').click()">
          <i class="ri-image-add-line" style="font-size:28px;color:var(--primary);"></i>
          <p style="margin:6px 0 2px;font-weight:600;font-size:13px;">Klik atau seret foto ke sini</p>
          <input type="file" id="field_flyer" name="flyer" accept="image/*" style="display:none" onchange="handleFileSelect(this.files)">
        </div>
        <div class="preview-grid" id="previewGrid"></div>
      </div>

      <div class="modal-footer" style="padding-left:0; padding-right:0;">
        <button type="submit" id="addVideoSubmitBtn" class="btn btn-primary">Simpan</button>
      </div>
    </form>
    </div>

    <div>
      <h3 style="margin-bottom:12px;">Judul Video</h3>
      <input type="text" id="judulVideoPreview" readonly style="width:100%; background:#f1f3f5; color:#212529; border:1px solid var(--border-color); border-radius:4px; padding:10px 12px; font-size:14px;">
      <button type="button" class="btn btn-primary" style="margin-top:10px; background-color:#198754;" onclick="copyJudul()">Salin Judul</button>

      <h3 style="margin:24px 0 12px;">Preview Deskripsi Youtube</h3>
      <textarea id="descPreview" readonly style="width:100%; height:460px; font-family:Courier, monospace; background:#f1f3f5; color:#212529; border:1px solid var(--border-color); border-radius:4px; padding:12px; resize:none; font-size:13px; line-height:1.5;"></textarea>
      <button type="button" class="btn btn-primary" style="margin-top:10px; background-color:#198754;" onclick="copyDescription()">Salin Deskripsi</button>
    </div>
  </div>
</div>

<div id="descToast" style="display:none; position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 16px; border-radius:4px; font-size:14px; z-index:1000;">Teks berhasil disalin ke clipboard!</div>

<script>

// ===== Tambahkan State untuk file uploads (Kloning dari troubleshoot.php) =====
let selectedFiles = []; 
let flyerBase64Data = null; 
let flyerFileName = '';
let editingPageId = null;

// ===== Master Data (Nama Acara & Nama Pemandu per Programa) =====
// Perbaikan: Ubah 'teknik' dan 'pa' menjadi array tunggal 'petugas' sesuai JSON
let masterData = { acara: { 'Pro 1': [], 'Pro 2': [], 'Pro 4': [] }, pemandu: { 'Pro 1': [], 'Pro 2': [], 'Pro 4': [] }, petugas: [] };
let masterDataLoaded = false;

async function loadMasterData(){
    try {
        const res = await fetch('../api/get-master-data.php');
        const data = await res.json();
        if (data.success) {
            // Perbaikan: Ambil data.petugas langsung dari hasil respons JSON
            masterData = { acara: data.acara, pemandu: data.pemandu, petugas: data.petugas };
            initFilterPetugas(); // Inisialisasi dropdown filter petugas setelah data dimuat
        } else {
            console.error('Gagal memuat master data:', data.error);
        }
    } catch (err) {
        console.error('Gagal memuat master data:', err);
    } finally {
        masterDataLoaded = true;
        // Isi dropdown sesuai Live Dimana yang sedang terpilih (kalau form sedang terbuka)
        populateProgramOptions();
        populatePenyiarOptions();
        populateTeknikOptions();
        populatePaOptions();
    }
}

// Fungsi untuk mengisi dropdown filter petugas saat master data selesai dimuat
function initFilterPetugas() {
    var select = document.getElementById('filterPetugas');
    var petugasList = masterData.petugas || []; // Mengambil dari objek masterData yang sudah ada
    
    petugasList.forEach(function(nama) {
        var opt = document.createElement('option');
        opt.value = nama;
        opt.textContent = nama;
        select.appendChild(opt);
    });
}

// Update fungsi filterTable untuk menangani select
function filterTable() {
    var programFilter = document.getElementById('filterPrograma').value.toLowerCase();
    var petugasFilter = document.getElementById('filterPetugas').value.toLowerCase();
    var rows = document.querySelectorAll('.video-row');

    rows.forEach(function(row) {
        var videoData = JSON.parse(row.dataset.video);
        
        var matchPrograma = programFilter === "" || videoData.programa.toLowerCase().includes(programFilter);
        
        // Memeriksa apakah nama petugas ada di dalam string PT atau PA
        var matchPetugas = petugasFilter === "" || 
                           videoData.teknik.toLowerCase().includes(petugasFilter) || 
                           videoData.pa.toLowerCase().includes(petugasFilter);

        row.style.display = (matchPrograma && matchPetugas) ? "" : "none";
    });
}

function getProgramaLabelFromLive(){
    var liveVal = document.getElementById('live').value;
    if (liveVal.indexOf('Pro 1') !== -1) return 'Pro 1';
    if (liveVal.indexOf('Pro 2') !== -1) return 'Pro 2';
    if (liveVal.indexOf('Pro 4') !== -1) return 'Pro 4';
    return 'Pro 1';
}

// Isi ulang dropdown Nama Acara sesuai Programa terpilih (murni dari master-data.json).
// selectedValue (opsional): value yang ingin dipertahankan/dipilih setelah dropdown diisi ulang.
function populateProgramOptions(selectedValue){
    var select = document.getElementById('program');
    var label = getProgramaLabelFromLive();
    var names = (masterData.acara && masterData.acara[label]) ? masterData.acara[label] : [];

    select.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = masterDataLoaded ? '— Pilih Nama Acara —' : 'Memuat daftar acara...';
    select.appendChild(placeholder);

    names.forEach(function(name){
        var opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        select.appendChild(opt);
    });

    if (selectedValue && Array.from(select.options).some(function(o){ return o.value === selectedValue; })) {
        select.value = selectedValue;
    }
}

// Isi ulang dropdown Nama Pemandu sesuai Programa terpilih (murni dari master-data.json).
function populatePenyiarOptions(selectedValue){
    var select = document.getElementById('penyiar');
    var label = getProgramaLabelFromLive();
    var names = (masterData.pemandu && masterData.pemandu[label]) ? masterData.pemandu[label] : [];

    select.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = masterDataLoaded ? '— Pilih Nama Pemandu —' : 'Memuat daftar pemandu...';
    select.appendChild(placeholder);

    names.forEach(function(name){
        var opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        select.appendChild(opt);
    });

    if (selectedValue && Array.from(select.options).some(function(o){ return o.value === selectedValue; })) {
        select.value = selectedValue;
    }
}

// Isi ulang dropdown multi-select Petugas Teknik (PT) sesuai Programa terpilih.
// --- FUNGSI CUSTOM MULTISELECT DROPDOWN (TAMPILAN SEPERTI GAMBAR) ---
function createCustomMultiSelect(containerId, hiddenInputId, options) {
    var container = document.getElementById(containerId);
    container.innerHTML = '';
    container.style.position = 'relative';
    container.style.width = '100%';

    var hiddenInput = document.getElementById(hiddenInputId);
    
    // Ambil nilai yang mungkin sudah ada (misal saat edit video)
    var selectedValues = hiddenInput.value ? hiddenInput.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : [];

    // 1. Kotak Pilih (Visual pengganti select)
    var selectBox = document.createElement('div');
    selectBox.style.cssText = 'min-height:38px; border:1px solid var(--border-color, #ced4da); border-radius:4px; padding:6px 30px 6px 8px; background:#fff; cursor:pointer; display:flex; flex-wrap:wrap; align-items:center; position:relative; gap:4px;';

    // Panah dropdown
    var arrow = document.createElement('div');
    arrow.innerHTML = '▼';
    arrow.style.cssText = 'position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:10px; color:#6c757d;';
    selectBox.appendChild(arrow);

    // 2. Menu Dropdown (Search + Checkbox List)
    var dropdown = document.createElement('div');
    dropdown.className = 'custom-dropdown-menu'; // Untuk identifikasi saat klik di luar
    dropdown.style.cssText = 'position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #ced4da; border-radius:4px; margin-top:2px; z-index:9999; display:none; box-shadow:0 4px 6px rgba(0,0,0,0.1);';

    // Baris Search
    var searchDiv = document.createElement('div');
    searchDiv.style.cssText = 'padding:8px; border-bottom:1px solid #eee;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchInput.style.cssText = 'width:100%; padding:6px 10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; outline:none; font-size:13px;';
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);

    // Daftar List
    var listDiv = document.createElement('div');
    listDiv.style.cssText = 'max-height:200px; overflow-y:auto; padding:4px 0;';
    dropdown.appendChild(listDiv);

    container.appendChild(selectBox);
    container.appendChild(dropdown);

    // Render Tag / Chip di kotak pilih
    function renderChips() {
        // Bersihkan area selectBox (kecuali panah)
        var elementsToRemove = [];
        for(var i = 0; i < selectBox.children.length; i++) {
            if (selectBox.children[i] !== arrow) elementsToRemove.push(selectBox.children[i]);
        }
        elementsToRemove.forEach(function(el){ selectBox.removeChild(el); });

        if (selectedValues.length === 0) {
            var placeholder = document.createElement('span');
            placeholder.textContent = 'Pilih petugas...';
            placeholder.style.cssText = 'color:#6c757d; font-size:14px;';
            selectBox.insertBefore(placeholder, arrow);
        } else {
            selectedValues.forEach(function(val) {
                var chip = document.createElement('span');
                // Styling mirip tag/chip seperti di referensi gambar
                chip.style.cssText = 'background:#e2e8f0; color:#334155; padding:2px 8px; border-radius:4px; font-size:12px; display:inline-flex; align-items:center; gap:6px; border:1px solid #cbd5e1;';
                chip.textContent = val;
                
                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'cursor:pointer; font-size:14px; font-weight:bold; color:#64748b;';
                closeBtn.onclick = function(e) {
                    e.stopPropagation();
                    toggleValue(val, false);
                };
                chip.appendChild(closeBtn);
                selectBox.insertBefore(chip, arrow);
            });
        }
        
        hiddenInput.value = selectedValues.join(', ');
        if (typeof generateDescription === 'function') generateDescription();
    }

    // Toggle (Centang / Hapus Centang)
    function toggleValue(val, isChecked) {
        if (isChecked && !selectedValues.includes(val)) {
            selectedValues.push(val);
        } else if (!isChecked) {
            selectedValues = selectedValues.filter(function(v){ return v !== val; });
        }
        renderChips();
        renderList(searchInput.value); // Update centang di list
    }

    // Render daftar nama (dengan filter search)
    // Render daftar nama (dengan filter search)
    function renderList(filterText) {
        if (!filterText) filterText = '';
        listDiv.innerHTML = '';
        listDiv.style.textAlign = 'left'; // Paksa bungkus utama ke kiri
        
        var filtered = options.filter(function(o){ 
            return o.toLowerCase().indexOf(filterText.toLowerCase()) !== -1; 
        });
        
        if (filtered.length === 0) {
            var empty = document.createElement('div');
            empty.textContent = 'Tidak ditemukan';
            empty.style.cssText = 'padding:8px; color:#999; text-align:center; font-size:13px;';
            listDiv.appendChild(empty);
            return;
        }

        filtered.forEach(function(opt) {
            var label = document.createElement('label');
            // Gunakan !important, width: 100%, dan flex-direction: row untuk melawan CSS template
            label.style.cssText = 'display:flex !important; flex-direction:row !important; align-items:center !important; justify-content:flex-start !important; text-align:left !important; padding:6px 12px !important; cursor:pointer !important; font-size:14px !important; margin:0 !important; width:100% !important; box-sizing:border-box !important; transition:background 0.2s;';
            label.onmouseover = function() { label.style.background = '#f8f9fa'; };
            label.onmouseout = function() { label.style.background = 'transparent'; };

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = opt;
            cb.checked = selectedValues.includes(opt);
            // Kunci posisi checkbox persis di ujung kiri
            cb.style.cssText = 'margin:0 10px 0 0 !important; position:static !important; float:none !important; width:auto !important; flex-shrink:0 !important;';
            
            cb.onchange = function(e) {
                toggleValue(opt, e.target.checked);
            };

            // Bungkus teks dalam span agar tidak terpengaruh margin label bawaan
            var textSpan = document.createElement('span');
            textSpan.textContent = opt;
            textSpan.style.cssText = 'flex-grow:1 !important; text-align:left !important; margin:0 !important;';

            label.appendChild(cb);
            label.appendChild(textSpan); 
            listDiv.appendChild(label);
        });
    }

    // Event Klik untuk Buka/Tutup Dropdown
    selectBox.onclick = function() {
        var isOpen = dropdown.style.display === 'block';
        
        // Tutup semua dropdown custom lain yang sedang terbuka
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ d.style.display = 'none'; });
        
        if (!isOpen) {
            dropdown.style.display = 'block';
            searchInput.focus();
            renderList(searchInput.value);
        }
    };

    // Event Ketik di Search
    searchInput.oninput = function(e) {
        renderList(e.target.value);
    };

    renderChips();
    renderList('');
}

// Tutup dropdown jika user klik di luar area
document.addEventListener('click', function(e) {
    if (!e.target.closest('#teknikContainer') && !e.target.closest('#paContainer')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(function(d){ 
            d.style.display = 'none'; 
        });
    }
});

// Panggil fungsi pembuat UI di fungsi standar yang sudah dipanggil sistem Anda
function populateTeknikOptions() {
    var names = (masterData.petugas) ? masterData.petugas : [];
    createCustomMultiSelect('teknikContainer', 'teknik', names);
}

function populatePaOptions() {
    var names = (masterData.petugas) ? masterData.petugas : [];
    createCustomMultiSelect('paContainer', 'pa', names);
}

// Dipanggil saat Live Dimana (Programa) berubah: isi ulang dropdown Nama Acara & Nama Pemandu
function handleLiveChange(){
    populateProgramOptions();
    populatePenyiarOptions();
    populateTeknikOptions();
    populatePaOptions();
}

document.addEventListener('DOMContentLoaded', () => {
  loadMasterData();

  const dropzone = document.getElementById('dropzone');
  if (dropzone) {
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
    dropzone.addEventListener('drop', e => {
      e.preventDefault();
      dropzone.classList.remove('dragover');
      handleFileSelect(e.dataTransfer.files);
    });
  }
});

// ===== Handle Pilihan File dari Dropzone =====
function handleFileSelect(files) {
  selectedFiles = []; // Karena flyer video hanya butuh 1, kita reset setiap pilih baru
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue;
    if (file.size > 10 * 1024 * 1024) { alert('⚠️ ' + file.name + ' melebihi 10MB'); continue; }
    selectedFiles.push(file);
  }
  renderPreviews();
}

function renderPreviews() {
  const grid = document.getElementById('previewGrid');
  grid.innerHTML = '';
  selectedFiles.forEach((file, idx) => {
    const div = document.createElement('div');
    div.className = 'preview-item';
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    const btn = document.createElement('button');
    btn.className = 'remove-btn';
    btn.innerHTML = '&times;';
    btn.onclick = (e) => { e.stopPropagation(); selectedFiles.splice(idx, 1); renderPreviews(); };
    div.appendChild(img);
    div.appendChild(btn);
    grid.appendChild(div);
  });
}

// ===== Fungsi Asynchronous Upload (Sesuai spesifikasi upload-to-notion.php Anda) =====
async function uploadFiles(files) {
  if (files.length === 0) return [];
  const formData = new FormData();
  
  // SINKRONISASI: Menggunakan key 'dokumentasi[]' agar terbaca oleh file upload-to-notion.php Anda
  files.forEach(f => formData.append('dokumentasi[]', f)); 
  
  const res = await fetch('../api/upload-to-notion.php', { method: 'POST', body: formData });
  const data = await res.json();
  if (!data.success) throw new Error(data.error || 'Upload ke Notion gagal');
  
  // Mengembalikan data hasil upload dari Notion (id, original)
  return data.files.map(f => ({ id: f.id, name: f.original })); 
}

function formatJamSistem(timeString){
    if (!timeString) return '00.00';
    return timeString.replace(':', '.');
}

// Kebalikan dari format gabungan "00.00-00.00" yang disimpan di Notion:
// pecah jadi { mulai, selesai } dalam format "HH:MM" untuk <input type="time">.
function parseJamGabungToInputs(gabung){
    if (!gabung) return { mulai: '', selesai: '' };
    var toHHMM = function(s){
        s = String(s).trim();
        var m = s.match(/^(\d{1,2})[.:](\d{2})$/);
        if (!m) return '';
        var hh = m[1].length < 2 ? '0' + m[1] : m[1];
        return hh + ':' + m[2];
    };
    var parts = String(gabung).split('-');
    return {
        mulai: toHHMM(parts[0]),
        selesai: parts.length > 1 ? toHHMM(parts[1]) : ''
    };
}

function formatTanggalIndonesia(dateString){
    if (!dateString) return '-';
    var date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    var bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return date.getDate() + ' ' + bulanIndo[date.getMonth()] + ' ' + date.getFullYear();
}

// Kebalikan dari formatTanggalIndonesia: menerima ISO ("2026-07-17"),
// format Indonesia ("17 Juli 2026"), atau string tanggal lain yang bisa
// di-parse Date(), lalu selalu mengembalikan "YYYY-MM-DD" untuk <input type="date">.
function parseTanggalToISO(str){
    if (!str) return '';
    str = String(str).trim();

    var isoMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) return isoMatch[1] + '-' + isoMatch[2] + '-' + isoMatch[3];

    var bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var parts = str.split(' ');
    if (parts.length === 3) {
        var day = parseInt(parts[0], 10);
        var monthIdx = bulanIndo.findIndex(function(m){ return m.toLowerCase() === parts[1].toLowerCase(); });
        var year = parseInt(parts[2], 10);
        if (!isNaN(day) && monthIdx !== -1 && !isNaN(year)) {
            var mm = (monthIdx + 1) < 10 ? '0' + (monthIdx + 1) : '' + (monthIdx + 1);
            var dd = day < 10 ? '0' + day : '' + day;
            return year + '-' + mm + '-' + dd;
        }
    }

    var d = new Date(str);
    if (!isNaN(d.getTime())) {
        var yyyy = d.getFullYear();
        var mo = (d.getMonth() + 1) < 10 ? '0' + (d.getMonth() + 1) : '' + (d.getMonth() + 1);
        var da = d.getDate() < 10 ? '0' + d.getDate() : '' + d.getDate();
        return yyyy + '-' + mo + '-' + da;
    }
    return '';
}

function setAddVideoDefaults(){
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = today.getMonth() + 1;
    var dd = today.getDate();
    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;

    document.getElementById('tanggal').value = yyyy + '-' + mm + '-' + dd;

    var currentHour = today.getHours();
    var nextHour = (currentHour + 1) % 24;
    var formatHH = currentHour < 10 ? '0' + currentHour : currentHour;
    var formatNextHH = nextHour < 10 ? '0' + nextHour : nextHour;

    document.getElementById('jamMulai').value = formatHH + ':00';
    document.getElementById('jamSelesai').value = formatNextHH + ':00';
}

function updateJudulVideo(){
    var programSelect = document.getElementById('program');
    var tema = document.getElementById('tema').value.trim();

    var namaAcaraDisplay = programSelect.selectedIndex >= 0 ? programSelect.options[programSelect.selectedIndex].text : '';
    if (programSelect.value === '') namaAcaraDisplay = '';

    var judul = tema !== '' ? tema : namaAcaraDisplay;
    document.getElementById('judulVideo').value = judul;
    document.getElementById('judulVideoPreview').value = judul;
    document.getElementById('namaAcaraDisplayHidden').value = namaAcaraDisplay;
}

function generateDescription(){
    updateJudulVideo();

    var programFinal = document.getElementById('program').value;
    var programTitleCase = programFinal.toUpperCase();
    var tema = document.getElementById('tema').value.trim();

    const tanggalMentah = document.getElementById('tanggal').value;
    var hari = '';
    if (tanggalMentah) {
        const dateObj = new Date(tanggalMentah);
        if (!isNaN(dateObj.getTime())) {
            const daftarHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            hari = daftarHari[dateObj.getDay()];
        }
    }

    var tanggalFormatted = formatTanggalIndonesia(tanggalMentah);
    var hariTanggalGabung = hari + ', ' + tanggalFormatted;

    var jamMulaiMentah = document.getElementById('jamMulai').value;
    var jamSelesaiMentah = document.getElementById('jamSelesai').value;
    var jamMulai = formatJamSistem(jamMulaiMentah);
    var jamSelesai = formatJamSistem(jamSelesaiMentah);

    var liveSelection = document.getElementById('live').value;
    var liveText = '';
    if (liveSelection.indexOf('94,6 FM Pro 1') !== -1) {
        liveText = 'Live di 94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official';
    } else if (liveSelection.indexOf('87.9 FM Pro 2') !== -1) {
        liveText = 'Live di 87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official';
    } else {
        liveText = 'Live di 105,3 FM Pro 4 RRI Malang dan YouTube RRI Malang Official';
    }

    var jenisPenyiar = document.getElementById('jenisPenyiar').value;
    var penyiar = document.getElementById('penyiar').value || '-';
    var narasumber = document.getElementById('narasumber').value.trim();
    var teknik = document.getElementById('teknik').value || '-';
    var pa = document.getElementById('pa').value || '-';

    var template = '';
    if (programTitleCase === 'DIALOG MALANG MENYAPA') {
        template += 'Prioritas Nasional\n';
    }

    template += programTitleCase + '\n';
    template += hariTanggalGabung + '\n';
    template += 'Pukul ' + jamMulai + ' s/d ' + jamSelesai + ' WIB\n';
    template += liveText + '\n\n';

    if (tema !== '') {
        template += 'Tema : " ' + tema + ' "\n\n';
    }
    template += jenisPenyiar + ' : ' + penyiar + '\n\n';
    if (narasumber !== '') {
        template += 'Narasumber :\n' + narasumber + '\n\n';
    }

    template += 'Pengarah Teknik : ' + teknik + '\n';
    template += 'Pengarah Acara : ' + pa + '\n\n';

    template += 'Dapatkan informasi terupdate dan hiburan terkini hanya di Radio Republik Indonesia\n';
    template += 'Programa 1 FM 94.6 Mhz & AM 891 KHz\n';
    template += 'Programa 2 FM 87.9 Mhz\n';
    template += 'Programa 4 FM 105.3 Mhz\n';
    template += 'Aplikasi RRI Digital dan Kanal rri.co.id\n';
    template += 'Youtube RRI MALANG Official';

    document.getElementById('descPreview').value = template;
}

function copyDescription(){
    var previewTextArea = document.getElementById('descPreview');
    previewTextArea.select();
    previewTextArea.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(previewTextArea.value).then(function(){
        var toast = document.getElementById('descToast');
        toast.style.display = 'block';
        setTimeout(function(){ toast.style.display = 'none'; }, 2500);
    });
}

function copyJudul(){
    var judulField = document.getElementById('judulVideoPreview');
    judulField.select();
    judulField.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(judulField.value).then(function(){
        var toast = document.getElementById('descToast');
        toast.style.display = 'block';
        setTimeout(function(){ toast.style.display = 'none'; }, 2500);
    });
}

function showAddView(){
    editingPageId = null;
    document.getElementById('addVideoTitle').textContent = 'Tambah Video';
    document.getElementById('addVideoSubmitBtn').textContent = 'Simpan';

    document.getElementById('galleryMainView').style.display = 'none';
    document.getElementById('addVideoView').style.display = 'block';
    window.scrollTo(0, 0);

    // Pastikan form benar-benar kosong walau sebelumnya sempat dipakai untuk Edit.
    // Jangan andalkan hideAddView() sudah dipanggil duluan -- reset di sini juga.
    document.getElementById('addVideoForm').reset();
    document.getElementById('teknik').value = '';
    document.getElementById('pa').value = '';
    selectedFiles = [];
    document.getElementById('previewGrid').innerHTML = '';

    setAddVideoDefaults();
    populateProgramOptions();
    populatePenyiarOptions();
    populateTeknikOptions();
    populatePaOptions();
    generateDescription();
}

function openEditView(videoData){
  console.log("Data Video dari Notion:", videoData);
    editingPageId = videoData.id;

    document.getElementById('galleryMainView').style.display = 'none';
    document.getElementById('addVideoView').style.display = 'block';
    window.scrollTo(0, 0);

    document.getElementById('addVideoTitle').textContent = 'Edit Video';
    document.getElementById('addVideoSubmitBtn').textContent = 'Simpan Perubahan';

    document.getElementById('addVideoForm').reset();
    selectedFiles = [];
    document.getElementById('previewGrid').innerHTML = '';

    // ---- Live Dimana (Programa) : diset LEBIH DULU karena menentukan isi dropdown Acara & Pemandu ----
    var liveSelect = document.getElementById('live');
    var liveIndexByProgram = { 'Pro 1': 0, 'Pro 2': 1, 'Pro 4': 2 };
    if (videoData.programa in liveIndexByProgram) {
        liveSelect.selectedIndex = liveIndexByProgram[videoData.programa];
    }

    // ---- Program / Nama Acara (dropdown diisi sesuai Programa di atas) ----
    populateProgramOptions();
    var programSelect = document.getElementById('program');
    var namaAcara = videoData.namaAcara || '';
    var matchedOption = Array.from(programSelect.options).find(function(opt){
        return opt.value !== '' && opt.text === namaAcara;
    });

    if (matchedOption) {
        programSelect.value = matchedOption.value;
    } else if (namaAcara !== '') {
        // Nama acara ini belum terdaftar di master-data.json -- tetap tampilkan
        // apa adanya supaya data lama tidak hilang saat diedit.
        var extraOpt = document.createElement('option');
        extraOpt.value = namaAcara;
        extraOpt.textContent = namaAcara + ' (belum ada di daftar)';
        programSelect.appendChild(extraOpt);
        programSelect.value = namaAcara;
    }

    // ---- Pemandu Acara (dropdown diisi sesuai Programa di atas) ----
    populatePenyiarOptions();
    var penyiarSelect = document.getElementById('penyiar');
    var namaPenyiar = videoData.penyiar || '';
    var matchedPenyiar = Array.from(penyiarSelect.options).find(function(opt){
        return opt.value !== '' && opt.value === namaPenyiar;
    });
    if (matchedPenyiar) {
        penyiarSelect.value = namaPenyiar;
    } else if (namaPenyiar !== '') {
        // Nama pemandu ini belum terdaftar di master-data.json -- tetap tampilkan
        // apa adanya supaya data lama tidak hilang saat diedit.
        var extraPenyiarOpt = document.createElement('option');
        extraPenyiarOpt.value = namaPenyiar;
        extraPenyiarOpt.textContent = namaPenyiar + ' (belum ada di daftar)';
        penyiarSelect.appendChild(extraPenyiarOpt);
        penyiarSelect.value = namaPenyiar;
    }

    // ---- Petugas Teknik & PA ----
    // 1. Masukkan data dari database ke dalam hidden input
    document.getElementById('teknik').value = videoData.teknik || '';
    document.getElementById('pa').value = videoData.pa || '';

    // 2. Baru panggil pembentuk UI (skrip kita otomatis membaca hidden input di atas)
    populateTeknikOptions();
    populatePaOptions();

    // // ---- Centang Checkbox Petugas Teknik & PA Saat Edit ----
    // if (videoData.teknik) {
    //     var arrTeknik = videoData.teknik.split(',').map(function(s){ return s.trim(); });
    //     var cbsTeknik = document.getElementById('teknikSelectContainer').querySelectorAll('input[type="checkbox"]');
    //     cbsTeknik.forEach(function(cb) {
    //         if (arrTeknik.includes(cb.value)) cb.checked = true;
    //     });
    //     updateTeknikHidden();
    // }

    // if (videoData.pa) {
    //     var arrPa = videoData.pa.split(',').map(function(s){ return s.trim(); });
    //     var cbsPa = document.getElementById('paSelectContainer').querySelectorAll('input[type="checkbox"]');
    //     cbsPa.forEach(function(cb) {
    //         if (arrPa.includes(cb.value)) cb.checked = true;
    //     });
    //     updatePaHidden();
    // }

    // ---- Tema (diambil langsung dari properti "Tema Acara" di Notion) ----
    var tema = videoData.tema || '';

    if (tema.trim() === '' && videoData.title) {
        // Fallback untuk data lama yang belum punya properti Tema Acara terisi --
        // coba turunkan dari judul "Nama Acara - Tema".
        var rawTitle = videoData.title;
        var normNamaAcara = (namaAcara || '').trim();
        var exactRegex = normNamaAcara !== ''
            ? new RegExp('^' + normNamaAcara.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*[-–—]\\s*')
            : null;

        if (exactRegex && exactRegex.test(rawTitle)) {
            tema = rawTitle.replace(exactRegex, '');
        } else {
            var sepMatch = rawTitle.match(/\s[-–—]\s/);
            if (sepMatch) {
                tema = rawTitle.substring(sepMatch.index + sepMatch[0].length);
            }
        }
    }
    document.getElementById('tema').value = tema.trim();

    // ---- Tanggal & Jam ----
    document.getElementById('tanggal').value = parseTanggalToISO(videoData.date);
    var jamParsed = parseJamGabungToInputs(videoData.time);
    document.getElementById('jamMulai').value = jamParsed.mulai;
    document.getElementById('jamSelesai').value = jamParsed.selesai;

    // ---- Narasumber ----
    document.getElementById('narasumber').value = Array.isArray(videoData.narasumber) ? videoData.narasumber.join('\n') : '';

    // ---- Link Youtube ----
    var linkInput = document.querySelector('#addVideoForm input[name="link"]');
    if (linkInput) linkInput.value = (videoData.url && videoData.url !== '#') ? videoData.url : '';

    // ---- Flyer: tampilkan yang sudah ada, ganti hanya jika user pilih foto baru ----
    if (videoData.flyer) {
        var grid = document.getElementById('previewGrid');
        grid.innerHTML = '';
        var wrapper = document.createElement('div');
        wrapper.className = 'preview-item';
        var img = document.createElement('img');
        img.src = videoData.flyer;
        wrapper.appendChild(img);
        grid.appendChild(wrapper);

        var hint = document.createElement('p');
        hint.style.cssText = 'font-size:12px; color:var(--text-secondary); margin-top:6px;';
        hint.textContent = 'Flyer saat ini. Pilih foto baru di atas untuk menggantinya, atau biarkan untuk tetap memakai flyer ini.';
        document.getElementById('dropzone').insertAdjacentElement('afterend', hint);
    }

    generateDescription();
}

function hideAddView(){
    document.getElementById('addVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
    document.getElementById('addVideoForm').reset();

    selectedFiles = [];
    flyerBase64Data = null;
    flyerFileName = '';
    document.getElementById('previewGrid').innerHTML = '';

    var dropzoneHint = document.querySelector('#dropzone + p');
    if (dropzoneHint) dropzoneHint.remove();

    editingPageId = null;
    document.getElementById('addVideoTitle').textContent = 'Tambah Video';
    document.getElementById('addVideoSubmitBtn').textContent = 'Simpan';
}

// UBAH MENJADI ASYNC FUNCTION
async function submitAddVideo(){
    var form = document.getElementById('addVideoForm');
    var formData = new FormData(form);
    var isEditMode = !!editingPageId;

    var submitBtn = form.querySelector('button[type="submit"]');
    var originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengupload Flyer...';

    // 1. Ekstrak form HTML menjadi objek plain JSON
    var plainObject = {};
    formData.forEach(function(value, key){
        if (key !== 'flyer') { 
            plainObject[key] = value;
        }
    });

    try {
        let uploadedFilesPayload = [];
        
        // 2. Jalankan pre-upload ke Notion via AWS S3 (jika ada file baru di dropzone)
        if (selectedFiles.length > 0) {
            submitBtn.textContent = 'Menyimpan Gambar ke Notion S3...';
            uploadedFilesPayload = await uploadFiles(selectedFiles);
        }

        // Simpan hasilnya ke key 'flyer_urls'. Saat edit dan tidak ada file baru,
        // array ini kosong -> flyer lama di Notion tidak akan diubah.
        plainObject['flyer_urls'] = uploadedFilesPayload;
        submitBtn.textContent = 'Menyimpan ke Database...';

        var endpoint = '../api/add-video.php';
        if (isEditMode) {
            plainObject['page_id'] = editingPageId;
            endpoint = '../api/edit-video.php';
        }

        // 3. Kirim data teks + manifes ID file dalam format JSON murni
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(plainObject)
        });

        const data = await res.json();
        if (data.success) {
            alert(isEditMode ? 'Perubahan berhasil disimpan.' : 'Video berhasil disimpan ke Notion.');
            hideAddView();
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + (data.error || data.details || 'Kesalahan tidak diketahui'));
        }
    } catch (err) {
        console.error('Error:', err);
        alert('Terjadi kesalahan: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
}

// Hapus video langsung dari baris tabel (tanpa buka modal detail)
function deleteVideoRow(pageId, title) {
    if (!confirm(`Hapus video "${title}" dari rekap?`)) return;

    fetch('../api/delete-video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page_id: pageId })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Video berhasil dihapus.');
            location.reload();
        } else {
            alert('Gagal menghapus: ' + (data.error || data.details || 'Kesalahan tidak diketahui'));
        }
    })
    .catch(function(err) {
        alert('Terjadi kesalahan jaringan: ' + err);
    });
}

function filterTable() {
    var programFilter = document.getElementById('filterPrograma').value.toLowerCase();
    var petugasFilter = document.getElementById('filterPetugas').value.toLowerCase();
    var rows = document.querySelectorAll('.video-row');

    rows.forEach(function(row) {
        var videoData = JSON.parse(row.dataset.video);
        
        // Cek Programa
        var matchPrograma = programFilter === "" || videoData.programa.toLowerCase().includes(programFilter);
        
        // Cek Petugas (Gabungkan PT dan PA untuk pencarian)
        var petugasGabung = (videoData.teknik + " " + videoData.pa).toLowerCase();
        var matchPetugas = petugasFilter === "" || petugasGabung.includes(petugasFilter);

        if (matchPrograma && matchPetugas) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>