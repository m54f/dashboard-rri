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

<div class="gallery-grid" id="videoGrid">
  <?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem; width:100%;">
      ⚠️ <?= htmlspecialchars($errorMessage) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($videos)): ?>
    <div style="grid-column:1 / -1; text-align:center; color: var(--text-secondary); padding: 2rem;">
      Data video gallery belum tersedia.
    </div>
  <?php else: ?>
    <?php foreach ($videos as $video): ?>
      <?php
        $title = isset($video['title']) && $video['title'] !== '' ? $video['title'] : 'Tanpa Judul';
        $namaAcara = isset($video['namaAcara']) && $video['namaAcara'] !== '' ? $video['namaAcara'] : '';
        $programa = isset($video['programa']) && $video['programa'] !== '' ? $video['programa'] : 'Umum';
        $programaColor = isset($video['programaColor']) ? $video['programaColor'] : 'gray';
        $date = isset($video['date']) && $video['date'] !== '' ? $video['date'] : 'TBA';
        $time = isset($video['time']) && $video['time'] !== '' ? $video['time'] : 'TBA';
        $url = isset($video['url']) && $video['url'] !== '' ? $video['url'] : '';
        $flyer = isset($video['flyer']) && $video['flyer'] !== '' ? $video['flyer'] : '';
        $penyiar = isset($video['penyiar']) && $video['penyiar'] !== '' ? $video['penyiar'] : '';
        $narasumber = isset($video['narasumber']) && is_array($video['narasumber']) ? $video['narasumber'] : [];
        
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
        'programa' => $programa,
        'programaColor' => $programaColor,
        'flyer' => $flyer,
        'date' => $date,
        'time' => $time,
        'url' => $url,
        'penyiar' => $penyiar,
        'narasumber' => $narasumber
      ]), ENT_QUOTES, 'UTF-8'); ?>
      <div class="feed-card" data-video="<?= $videoData ?>" style="cursor:pointer; display:flex; flex-direction:column; height:100%;">
        <div class="feed-thumb" style="display:block; position:relative; overflow:hidden; background:#f0f0f0; min-height:200px;">
          <?php if ($flyer !== ''): ?>
            <img src="<?= htmlspecialchars($flyer, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" style="width:100%; height:100%; object-fit:cover;">
          <?php else: ?>
            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#999;">
              <i class="ri-image-line" style="font-size:3rem;"></i>
            </div>
          <?php endif; ?>
          <span class="overlay-label" style="position:absolute; top:10px; left:10px; background:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; color:#fff; padding:4px 12px; border-radius:4px; font-size:12px; font-weight:bold;">
            <?= htmlspecialchars($programa, ENT_QUOTES, 'UTF-8') ?>
          </span>
        </div>
        <div class="feed-info" style="flex:1; display:flex; flex-direction:column;">
          <div style="flex:1;">
            <?php if ($namaAcara !== ''): ?>
              <div style="font-size:12px; color:<?= htmlspecialchars($bgColor, ENT_QUOTES, 'UTF-8') ?>; font-weight:bold; margin-bottom:4px;">
                <?= htmlspecialchars($namaAcara, ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
            <div class="title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <div class="meta" style="font-size:13px; color:var(--text-secondary); border-top:1px solid var(--border-color); padding-top:12px; margin-top:12px;">
            <span><i class="ri-calendar-line"></i> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span>
            <span><i class="ri-time-line"></i> <?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
</div>

<div id="addVideoView" style="display:none;">
  <div class="page-head">
    <div>
      <button class="btn btn-secondary" onclick="hideAddView()"><i class="ri-arrow-left-line"></i> Kembali</button>
    </div>
  </div>
  <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">
    <div>
    <h2 style="margin-bottom:4px;">Tambah Video</h2>
    <p style="color:var(--text-secondary); margin-bottom:24px;">Isi detail video yang akan ditambahkan ke rekap.</p>

    <form id="addVideoForm" onsubmit="event.preventDefault(); submitAddVideo();">
      <div class="form-group">
        <label>Judul Video <span style="font-weight:400; color:var(--text-secondary); text-transform:none;">(otomatis: Nama Acara - Tema)</span></label>
        <input type="text" id="judulVideo" name="judul" readonly style="background:#f1f3f5; cursor:not-allowed;">
      </div>
      <input type="hidden" id="namaAcaraDisplayHidden" name="namaAcaraDisplay">

      <div class="form-group">
        <label>Nama Program / Acara</label>
        <div id="programContainer" class="form-row" style="grid-template-columns:1fr;">
          <select id="program" name="program" onchange="handleProgramChange(); generateDescription();" required>
            <option value="DIALOG MALANG MENYAPA">Dialog Malang Menyapa</option>
            <option value="MUTIARA PAGI">Mutiara Pagi</option>
            <option value="BERITA">Berita (Pagi/Siang/Malam)</option>
            <option value="CATUR LARAS (JAGONGAN UMKM)">Catur Laras (Jagongan UMKM)</option>
            <option value="JAGA MALAM">Jaga Malam</option>
            <option value="LAINNYA">Lainnya...</option>
          </select>

          <select id="waktuBerita" name="waktuBerita" style="display:none;" onchange="generateDescription()">
            <option value="PAGI">Pagi</option>
            <option value="SIANG">Siang</option>
            <option value="MALAM">Malam</option>
          </select>

          <input type="text" id="programKustom" name="programKustom" style="display:none;" placeholder="Ketik nama acara baru di sini" oninput="generateDescription()">
        </div>
      </div>

      <div class="form-group">
        <label>Tema Acara</label>
        <input type="text" id="tema" name="tema" placeholder="Contoh: MPLS Ramah 2026, Tanpa Perploncoan" oninput="generateDescription()">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tanggal</label>
          <input type="date" id="tanggal" name="tanggal" required oninput="generateDescription()">
        </div>
        <div class="form-group">
          <label>Hari</label>
          <input type="text" id="hari" readonly disabled style="background-color: #e9ecef; cursor: not-allowed;">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Jam Mulai</label><input type="time" id="jamMulai" name="jamMulai" required oninput="generateDescription()"></div>
        <div class="form-group"><label>Jam Selesai</label><input type="time" id="jamSelesai" name="jamSelesai" oninput="generateDescription()"></div>
      </div>

      <div class="form-group">
        <label>Live Dimana (Programa)</label>
        <select id="live" name="live" onchange="generateDescription()" required>
          <option value="94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official">Pro 1 FM 94.6 Mhz</option>
          <option value="87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official">Pro 2 FM 87.9 Mhz</option>
          <option value="105.3 FM Pro 4 RRI Malang dan Youtube RRI Malang Official">Pro 4 FM 105.3 Mhz</option>
        </select>
      </div>

      <div class="form-group">
        <label>Pemandu Acara</label>
        <div class="form-row" style="grid-template-columns:120px 1fr;">
          <select id="jenisPenyiar" name="jenisPenyiar" onchange="generateDescription()">
            <option value="Presenter">Presenter</option>
            <option value="Penyiar">Penyiar</option>
            <option value="Host">Host</option>
          </select>
          <input type="text" id="penyiar" name="penyiar" placeholder="Nama pengisi acara" oninput="generateDescription()">
        </div>
      </div>

      <div class="form-group">
        <label>Narasumber</label>
        <textarea id="narasumber" name="narasumber" rows="2" placeholder="1. Nama (Jabatan)&#10;2. Nama (Jabatan)" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:4px; font-size:14px;" oninput="generateDescription()"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Petugas Teknik (PT)</label>
          <input type="text" id="teknik" name="teknik" placeholder="Contoh: Yhanuar, Alif" oninput="generateDescription()">
        </div>
        <div class="form-group">
          <label>Petugas PA / Acara</label>
          <input type="text" id="pa" name="pa" placeholder="Contoh: Roni" oninput="generateDescription()">
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
        <button type="button" class="btn btn-secondary" onclick="hideAddView()">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
    </div>

    <div>
      <h3 style="margin-bottom:12px;">Preview Deskripsi Youtube</h3>
      <textarea id="descPreview" readonly style="width:100%; height:460px; font-family:Courier, monospace; background:#f1f3f5; color:#212529; border:1px solid var(--border-color); border-radius:4px; padding:12px; resize:none; font-size:13px; line-height:1.5;"></textarea>
      <button type="button" class="btn btn-primary" style="margin-top:10px; background-color:#198754;" onclick="copyDescription()">Salin Deskripsi</button>
    </div>
  </div>
</div>

<div id="descToast" style="display:none; position:fixed; bottom:20px; right:20px; background:#333; color:#fff; padding:10px 16px; border-radius:4px; font-size:14px; z-index:1000;">Teks berhasil disalin ke clipboard!</div>

<div class="modal-overlay" id="detailModal">
  <div class="modal-content">
    <div class="modal-header"><h3 class="modal-title">Detail Video</h3><button class="modal-close" onclick="closeDetailModal()">&times;</button></div>
    <div id="detailContent" style="padding:0;">
      <div style="max-height:500px; overflow-y:auto;">
        <!-- Flyer at top without label -->
        <div id="detail-flyer-group" style="display:none; margin-bottom:0;">
          <div style="max-height:300px; overflow:hidden;" id="detail-flyer"></div>
        </div>
        
        <!-- Content section -->
        <div style="padding:20px;">
          <div id="detail-title" style="font-size:16px; font-weight:700; color:var(--text-primary); margin-bottom:16px;"></div>
          
          <div id="detail-namaAcara-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Nama Acara</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-namaAcara"></div>
          </div>
          
          <div style="margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Program</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-programa"></div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:12px;">
            <div>
              <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Tanggal</div>
              <div style="font-size:14px; color:var(--text-primary);" id="detail-date"></div>
            </div>
            <div>
              <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Jam</div>
              <div style="font-size:14px; color:var(--text-primary);" id="detail-time"></div>
            </div>
          </div>

          <div id="detail-penyiar-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Penyiar</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-penyiar"></div>
          </div>

          <div id="detail-narasumber-group" style="display:none; margin-bottom:12px;">
            <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:4px;">Narasumber</div>
            <div style="font-size:14px; color:var(--text-primary);" id="detail-narasumber"></div>
          </div>
          
          <div id="detail-url-group" style="display:none; margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color);">
            <!-- <div style="font-size:12px; color:var(--text-secondary); font-weight:600; text-transform:uppercase; margin-bottom:8px;">Link Youtube</div> -->
            <div style="word-break:break-all;">
              <a id="detail-url" href="" target="_blank" style="color:var(--primary); text-decoration:none; font-size:13px;"></a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-danger" id="detail-delete-btn" style="background-color: var(--danger); background-color: #ef4444; color: white;" onclick="deleteVideoFromModal()"><i class="ri-delete-bin-line"></i> Hapus Video</button>
      <button type="button" class="btn btn-secondary" onclick="closeDetailModal()">Tutup</button>
      <a id="detail-open-url" href="" target="_blank" class="btn btn-primary" style="text-decoration:none; display:none;"><i class="ri-external-link-line"></i> Buka Video</a>
    </div>
  </div>
</div>
<script>

// ===== Tambahkan State untuk file uploads (Kloning dari troubleshoot.php) =====
let selectedFiles = []; 
let flyerBase64Data = null; 
let flyerFileName = '';

// ===== Drag & Drop Event Listeners =====
document.addEventListener('DOMContentLoaded', () => {
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

function handleProgramChange(){
    var programSelect = document.getElementById('program').value;
    var customInput = document.getElementById('programKustom');
    var beritaSelect = document.getElementById('waktuBerita');

    customInput.style.display = 'none';
    beritaSelect.style.display = 'none';

    if (programSelect === 'LAINNYA') {
        customInput.style.display = 'block';
        customInput.focus();
    } else if (programSelect === 'BERITA') {
        beritaSelect.style.display = 'block';
    }
}

function formatJamSistem(timeString){
    if (!timeString) return '00.00';
    return timeString.replace(':', '.');
}

function formatTanggalIndonesia(dateString){
    if (!dateString) return '-';
    var date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    var bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return date.getDate() + ' ' + bulanIndo[date.getMonth()] + ' ' + date.getFullYear();
}

function setAddVideoDefaults(){
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = today.getMonth() + 1;
    var dd = today.getDate();
    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;

    document.getElementById('tanggal').value = yyyy + '-' + mm + '-' + dd;

    var hariIndo = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    document.getElementById('hari').value = hariIndo[today.getDay()];

    var currentHour = today.getHours();
    var nextHour = (currentHour + 1) % 24;
    var formatHH = currentHour < 10 ? '0' + currentHour : currentHour;
    var formatNextHH = nextHour < 10 ? '0' + nextHour : nextHour;

    document.getElementById('jamMulai').value = formatHH + ':00';
    document.getElementById('jamSelesai').value = formatNextHH + ':00';
}

function updateJudulVideo(){
    var programSelect = document.getElementById('program');
    var customProgramText = document.getElementById('programKustom').value;
    var waktuBerita = document.getElementById('waktuBerita').value;
    var tema = document.getElementById('tema').value.trim();

    var namaAcaraDisplay = '';
    if (programSelect.value === 'LAINNYA') {
        namaAcaraDisplay = customProgramText.trim() || 'Nama Program Kustom';
    } else if (programSelect.value === 'BERITA') {
        var waktuLabel = waktuBerita.charAt(0) + waktuBerita.slice(1).toLowerCase();
        namaAcaraDisplay = 'Berita ' + waktuLabel;
    } else {
        namaAcaraDisplay = programSelect.options[programSelect.selectedIndex].text;
    }

    var judul = tema !== '' ? (namaAcaraDisplay + ' - ' + tema) : namaAcaraDisplay;
    document.getElementById('judulVideo').value = judul;
    document.getElementById('namaAcaraDisplayHidden').value = namaAcaraDisplay;
}

function generateDescription(){
    updateJudulVideo();

    var programSelect = document.getElementById('program').value;
    var customProgramText = document.getElementById('programKustom').value;
    var waktuBerita = document.getElementById('waktuBerita').value;

    var programFinal = '';
    if (programSelect === 'LAINNYA') {
        programFinal = customProgramText || 'Nama Program Kustom';
    } else if (programSelect === 'BERITA') {
        programFinal = 'BERITA ' + waktuBerita;
    } else {
        programFinal = programSelect;
    }

    var programTitleCase = programFinal.toUpperCase();
    var tema = document.getElementById('tema').value.trim();

    const tanggalMentah = document.getElementById('tanggal').value;
        if (tanggalMentah) {
            const dateObj = new Date(tanggalMentah);
            if (!isNaN(dateObj.getTime())) {
                const daftarHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                // Mengubah nilai elemen dropdown <select id="hari"> secara otomatis
                document.getElementById('hari').value = daftarHari[dateObj.getDay()];
            }
        }

    var hari = document.getElementById('hari').value;
    // var tanggalMentah = document.getElementById('tanggal').value;
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
    if (programSelect === 'DIALOG MALANG MENYAPA') {
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

function showAddView(){
    document.getElementById('galleryMainView').style.display = 'none';
    document.getElementById('addVideoView').style.display = 'block';
    window.scrollTo(0, 0);
    setAddVideoDefaults();
    handleProgramChange();
    generateDescription();
}

function hideAddView(){
    document.getElementById('addVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
    document.getElementById('addVideoForm').reset();
    handleProgramChange();
}

// UBAH MENJADI ASYNC FUNCTION
async function submitAddVideo(){
    var form = document.getElementById('addVideoForm');
    var formData = new FormData(form);

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
        
        // 2. Jalankan pre-upload ke Notion via AWS S3 (jika ada file di dropzone)
        if (selectedFiles.length > 0) {
            submitBtn.textContent = 'Menyimpan Gambar ke Notion S3...';
            uploadedFilesPayload = await uploadFiles(selectedFiles);
        }

        // Simpan hasilnya ke key 'flyer_urls'
        plainObject['flyer_urls'] = uploadedFilesPayload;
        submitBtn.textContent = 'Menyimpan ke Database...';

        // 3. Kirim data teks + manifes ID file dalam format JSON murni
        const res = await fetch('../api/add-video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(plainObject)
        });

        const data = await res.json();
        if (data.success) {
            alert('Video berhasil disimpan ke Notion.');
            hideAddView();
            location.reload();
        } else {
            alert('Gagal menyimpan: ' + (data.error || 'Kesalahan tidak diketahui'));
        }
    } catch (err) {
        console.error('Error:', err);
        alert('Terjadi kesalahan: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
}

function hideAddView(){
    document.getElementById('addVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
    document.getElementById('addVideoForm').reset();
    
    // Bersihkan data dropzone
    flyerBase64Data = null;
    flyerFileName = '';
    document.getElementById('previewGrid').innerHTML = '';
    
    handleProgramChange();
}

var activeNotionPageId = null;

function openDetailModal(videoData) {

    activeNotionPageId = videoData.id;

    document.getElementById('detail-title').textContent = videoData.title;
    
    var namaAcaraGroup = document.getElementById('detail-namaAcara-group');
    if (videoData.namaAcara && videoData.namaAcara !== '') {
        namaAcaraGroup.style.display = 'block';
        document.getElementById('detail-namaAcara').textContent = videoData.namaAcara;
    } else {
        namaAcaraGroup.style.display = 'none';
    }
    
    document.getElementById('detail-programa').textContent = videoData.programa;
    document.getElementById('detail-date').textContent = videoData.date;
    document.getElementById('detail-time').textContent = videoData.time;

    var penyiarGroup = document.getElementById('detail-penyiar-group');
    if (videoData.penyiar && videoData.penyiar !== '') {
        penyiarGroup.style.display = 'block';
        document.getElementById('detail-penyiar').textContent = videoData.penyiar;
    } else {
        penyiarGroup.style.display = 'none';
    }

    var narasumberGroup = document.getElementById('detail-narasumber-group');
    if (videoData.narasumber && Array.isArray(videoData.narasumber) && videoData.narasumber.length > 0) {
        narasumberGroup.style.display = 'block';
        var listEl = document.createElement('ul');
        listEl.style.margin = '0';
        listEl.style.paddingLeft = '18px';
        videoData.narasumber.forEach(function(nama) {
            var li = document.createElement('li');
            li.textContent = nama;
            listEl.appendChild(li);
        });
        var container = document.getElementById('detail-narasumber');
        container.innerHTML = '';
        container.appendChild(listEl);
    } else {
        narasumberGroup.style.display = 'none';
    }
    
    var flyerGroup = document.getElementById('detail-flyer-group');
    if (videoData.flyer && videoData.flyer !== '') {
        flyerGroup.style.display = 'block';
        document.getElementById('detail-flyer').innerHTML = '<img src="' + videoData.flyer + '" style="width:100%; height:auto;" alt="Flyer">';
    } else {
        flyerGroup.style.display = 'none';
    }
    
    var urlGroup = document.getElementById('detail-url-group');
    var openUrlBtn = document.getElementById('detail-open-url');
    if (videoData.url && videoData.url !== '' && videoData.url !== '#') {
        // urlGroup.style.display = 'block';
        document.getElementById('detail-url').href = videoData.url;
        document.getElementById('detail-url').textContent = videoData.url;
        openUrlBtn.href = videoData.url;
        openUrlBtn.style.display = 'inline-flex';
    } else {
        urlGroup.style.display = 'none';
        openUrlBtn.style.display = 'none';
    }
    
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal(){
    document.getElementById('detailModal').classList.remove('active');
    activeNotionPageId = null;
}

function deleteVideoFromModal() {
    if (!activeNotionPageId) {
        alert('ID Halaman Notion tidak ditemukan.');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menghapus video ini dari rekap?')) {
        return;
    }

    var deleteBtn = document.getElementById('detail-delete-btn');
    deleteBtn.disabled = true;
    deleteBtn.textContent = 'Menghapus...';

    fetch('../api/delete-video.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ page_id: activeNotionPageId })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert('Video berhasil dihapus.');
            closeDetailModal();
            location.reload(); // Muat ulang halaman galeri untuk memperbarui list data rekap
        } else {
            alert('Gagal menghapus: ' + (data.error || data.details || 'Kesalahan tidak diketahui'));
            deleteBtn.disabled = false;
            deleteBtn.textContent = 'Hapus Video';
        }
    })
    .catch(function(err) {
        alert('Terjadi kesalahan jaringan: ' + err);
        deleteBtn.disabled = false;
        deleteBtn.textContent = 'Hapus Video';
    });
}

function hideAddView(){
    document.getElementById('addVideoView').style.display = 'none';
    document.getElementById('galleryMainView').style.display = 'block';
    document.getElementById('addVideoForm').reset();
    
    // Tambahkan 2 baris pembersihan state ini:
    selectedFiles = [];
    document.getElementById('previewGrid').innerHTML = '';
    
    handleProgramChange();
}

// Modal overlay click handler
document.getElementById('detailModal').addEventListener('click', function(e) { 
    if (e.target === e.currentTarget) closeDetailModal(); 
});

// Video card click handler
document.querySelectorAll('.feed-card').forEach(function(card) {
    card.addEventListener('click', function() {
        var videoDataStr = this.getAttribute('data-video');
        if (videoDataStr) {
            try {
                var videoData = JSON.parse(videoDataStr);
                openDetailModal(videoData);
            } catch(e) {
                console.error('Error parsing video data:', e);
            }
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>