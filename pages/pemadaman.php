<?php
require_once __DIR__.'/../api/pemadaman.php';

$pemadaman = getPemadaman();

if (!is_array($pemadaman) || isset($pemadaman['error'])) {
    $errorMessage = $pemadaman['error'] ?? 'Gagal memuat data';
    $pemadaman = [];
}

$total      = count($pemadaman);
$studioN    = count(array_filter($pemadaman, fn($p) => strpos($p['pemancar'], 'Studio') !== false));
$ngadirejoN = count(array_filter($pemadaman, fn($p) => strpos($p['pemancar'], 'Ngadirejo') !== false));
$ngijoN     = count(array_filter($pemadaman, fn($p) => strpos($p['pemancar'], 'Ngijo') !== false));
$panelN     = count(array_filter($pemadaman, fn($p) => strpos($p['pemancar'], 'Auditorium') !== false || strpos($p['pemancar'], 'Panel') !== false));
$gensetN    = count(array_filter($pemadaman, fn($p) => strpos($p['pemancar'], 'Genset') !== false));

$stats = [
    ['label' => 'Total Log', 'value' => $total, 'sub' => 'Semua catatan', 'icon' => 'ri-flashlight-line', 'tone' => 'orange'],
    ['label' => 'Pemadaman PLN', 'value' => $studioN + $ngadirejoN + $ngijoN, 'sub' => 'Studio, Ngadirejo, Ngijo', 'icon' => 'ri-broadcast-line', 'tone' => 'blue'],
    ['label' => 'Panel Auditorium', 'value' => $panelN, 'sub' => 'Total kejadian', 'icon' => 'ri-plug-line', 'tone' => 'purple'],
    ['label' => 'Maintenance Genset', 'value' => $gensetN, 'sub' => 'Total kejadian', 'icon' => 'ri-tools-line', 'tone' => 'green'],
];

$base = '..';
$pageTitle = 'Logbook Listrik';
$activeMenu = 'pemadaman';

$petugasList = ['Alfian','Alif','Saiful','Winsa','Yayang','Yhanuar','Yuda','Deny','Selvy','Anggi','Dedy','Syukur'];

$pemancarList = [
    'Pemadaman PLN Pemancar Studio', 
    'Pemadaman PLN Pemancar Ngijo', 
    'Pemadaman PLN Pemancar Ngadirejo', 
    'Pemadaman PLN Pemancar Probolinggo',
    'Penggunaan Listrik Panel Auditorium',
    'Pemanasan Mesin Genset'
];

include __DIR__ . '/../includes/header.php';
?>

<!-- ===== STYLE KHUSUS MODAL MODERN ===== -->
<style>
/* ===== MODAL OVERLAY ===== */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
  opacity: 0;
  visibility: hidden;
  transition: opacity .25s ease, visibility .25s ease;
}
.modal-overlay.active {
  opacity: 1;
  visibility: visible;
}

/* ===== MODAL CONTAINER ===== */
.modal-content {
  background: #ffffff;
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0,0,0,.25);
  transform: translateY(20px);
  transition: transform .3s ease;
}
.modal-overlay.active .modal-content {
  transform: translateY(0);
}

/* ===== HEADER (tidak floating) ===== */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  background: #ffffff;          /* solid, tidak transparan */
  border-bottom: 1px solid #e5e7eb;
  position: relative;            /* hapus sticky/fixed */
  flex-shrink: 0;
}
.modal-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}
.modal-close {
  background: transparent;
  border: none;
  font-size: 1.6rem;
  line-height: 1;
  color: #6b7280;
  cursor: pointer;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  transition: background .2s, color .2s;
}
.modal-close:hover {
  background: #f3f4f6;
  color: #111827;
}

/* Hilangkan panah atas-bawah (spinner) pada input pagination */
.page-input-clean::-webkit-outer-spin-button,
.page-input-clean::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}
.page-input-clean {
  -moz-appearance: textfield !important;
  appearance: textfield !important;
}

/* Optimasi Responsif Layar Mobile & Tablet */
@media (max-width: 768px) {
  .page-head {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 12px !important;
    margin-bottom: 1rem !important;
  }
  .page-head > div:first-child {
    width: 100%;
  }
  .page-head .btn-primary {
    width: 100% !important;
    justify-content: center !important;
  }
  .filter-tabs {
    display: flex !important;
    overflow-x: auto !important;
    white-space: nowrap !important;
    flex-wrap: nowrap !important;
    padding-bottom: 8px !important;
    margin-bottom: 1rem !important;
    -webkit-overflow-scrolling: touch;
    gap: 8px !important;
  }
  .filter-tab {
    flex-shrink: 0 !important;
  }
  .table-wrap {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 12px 12px;
  }
  #pemadamanTable {
    min-width: 680px !important;
  }
  #pemadamanTable th, #pemadamanTable td {
    padding: 0.75rem 0.6rem !important;
    font-size: 0.83rem !important;
  }
  .pagination-wrap {
    flex-direction: column;
    align-items: stretch !important;
    text-align: center;
  }
}

@media (max-width: 640px) {
  .stats-row {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
  }
  .stats-row > *:last-child:nth-child(odd) {
    grid-column: span 2;
  }
}

/* ===== BODY (scroll di sini saja) ===== */
.modal-body {
  padding: 20px 22px;
  overflow-y: auto;
  flex: 1 1 auto;
  min-height: 0; /* TAMBAHKAN INI agar scrollbar berfungsi */
  background: #ffffff;
}

/* ===== FOOTER (menempel rapat, tidak melayang) ===== */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  background: #ffffff;          /* solid */
  border-top: 1px solid #e5e7eb;
  flex-shrink: 0;
  position: relative;            /* hapus sticky/fixed */
  margin-top: 0;                 /* hapus margin negatif jika ada */
}

/* ===== TOMBOL ===== */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 10px;
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: background .2s, transform .1s, box-shadow .2s;
}
.btn:active { transform: translateY(1px); }
.btn-secondary {
  background: #f1f5f9;
  color: #334155;
}
.btn-secondary:hover { background: #e2e8f0; }
.btn-primary {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 4px 12px rgba(37,99,235,.3);
}
.btn-primary:hover { background: #1d4ed8; }

/* Scrollbar tipis agar rapi */
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }


/* ===== FORM WRAPPER (Agar flexbox turun ke modal-body) ===== */
#pemadamanForm {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0; /* Wajib ada agar flex child bisa di-scroll */
  overflow: hidden;
}
</style>

<div class="page-head">
  <div>
    <h2>Logbook Aktivitas Listrik</h2>
    <p>Catat dan pantau riwayat pemadaman listrik, penggunaan panel, dan maintenance genset.</p>
  </div>
  <div class="page-head-actions">
    <div class="search-box page-head-search">
      <i class="ri-search-line"></i>
      <input type="text" id="pemadamanSearchInput" placeholder="Cari aktivitas/petugas..." oninput="filterPemadamanTable(this.value)" style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Catat Aktivitas</button>
  </div>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data dari Notion: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info">
      <h3><?= htmlspecialchars($s['label']) ?></h3>
      <div class="stat-value"><?= htmlspecialchars($s['value']) ?></div>
      <div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div>
    </div>
    <div class="stat-icon <?= $s['tone'] ?>"><i class="<?= $s['icon'] ?>"></i></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter Tab berdasarkan Pemancar -->
<div class="filter-tabs">
  <div class="filter-tab active" onclick="filterPemadaman('Semua', this)">Semua</div>
  <?php foreach ($pemancarList as $loc): ?>
  <div class="filter-tab" onclick="filterPemadaman('<?= htmlspecialchars($loc) ?>', this)"><?= htmlspecialchars($loc) ?></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-list-check-2"></i> Riwayat Aktivitas</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="pemadamanTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Kategori / Pemancar</th>
          <th>Tanggal & Waktu</th>
          <th>Durasi</th>
          <th>Petugas</th>
          <th>Bukti Foto</th>
          <th>Waktu Submit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pemadaman)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Tidak ada data ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pemadaman as $p):
            $editPayload = htmlspecialchars(json_encode([
                'page_id'      => $p['page_id'],
                'pemancar'     => $p['pemancar'],
                'tanggal_raw'  => $p['tanggal_raw'],
                'start_time'   => $p['start_time'] ?? '',
                'end_time'     => $p['end_time'] ?? '',
                'petugas'      => $p['petugas_arr'],
                'files'        => $p['files'] ?? [],
            ]), ENT_QUOTES, 'UTF-8');
            
            $durasi = '-';
            if (!empty($p['start_time']) && !empty($p['end_time'])) {
                $start = new DateTime($p['start_time']);
                $end = new DateTime($p['end_time']);
                $diff = $start->diff($end);
                $durasi = ($diff->h > 0 ? $diff->h . 'j ' : '') . $diff->i . 'm';
            }

            $tagColor = 'blue';
            if (strpos($p['pemancar'] ?? '', 'Panel') !== false) $tagColor = 'purple';
            if (strpos($p['pemancar'] ?? '', 'Genset') !== false) $tagColor = 'green';
          ?>
          <tr data-pemancar="<?= htmlspecialchars($p['pemancar']) ?>">
            <td style="font-weight:700; color:var(--text-secondary);"><?= htmlspecialchars($p['id']) ?></td>
            <td>
              <span class="tag <?= $tagColor ?>">
                <i class="ri-record-circle-line"></i> <?= htmlspecialchars($p['pemancar']) ?>
              </span>
            </td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($p['tanggal']) ?></div>
              <div style="font-size:11px; color:var(--text-secondary);">
                <?= htmlspecialchars($p['start_time'] ?? '') ?> - <?= htmlspecialchars($p['end_time'] ?? '') ?>
              </div>
            </td>
            <td><span class="mini-badge orange"><?= $durasi ?></span></td>
            <td><?= htmlspecialchars($p['petugas']) ?></td>
            <td>
              <?php if (!empty($p['files'])): 
                $fileUrls = array_map(function($f) { return $f['url']; }, $p['files']);
                $jsonUrls = htmlspecialchars(json_encode($fileUrls), ENT_QUOTES, 'UTF-8');
              ?>
                <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                  <?php foreach (array_slice($p['files'], 0, 3) as $idx => $file): ?>
                    <img src="<?= htmlspecialchars($file['url']) ?>" 
                         loading="lazy" 
                         alt="<?= htmlspecialchars($file['name']) ?>" 
                         title="<?= htmlspecialchars($file['name']) ?>"
                         class="doc-thumb"
                         onclick='openLightbox(<?= $jsonUrls ?>, <?= $idx ?>)'
                         style="width:40px; height:40px; object-fit:cover; border-radius:8px; cursor:pointer; border:1px solid var(--border-color, #e2e8f0); transition:transform 0.2s;"
                         onmouseover="this.style.transform='scale(1.08)'" 
                         onmouseout="this.style.transform='scale(1)'">
                  <?php endforeach; ?>
                  
                  <?php if (count($p['files']) > 3): ?>
                    <span style="font-size:12px; font-weight:700; color:var(--text-secondary, #475569); cursor:pointer; align-self:center;" onclick='openLightbox(<?= $jsonUrls ?>, 3)'>+<?= count($p['files']) - 3 ?></span>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span style="font-size:12px; color:#94a3b8;">-</span>
              <?php endif; ?>
            </td>
            <td class="cell-sub"><?= htmlspecialchars($p['submission']) ?></td>
            <td class="cell-actions">
              <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
              </a>
              <?php if (getRole() === 'admin'): ?>
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deletePemadaman('<?= htmlspecialchars($p['page_id']) ?>', '<?= htmlspecialchars($p['tanggal']) ?>')">
                <i class="ri-delete-bin-line"></i>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination-wrap" id="paginationWrap" style="display: none;">
    <div class="pagination-info" id="paginationInfo">Menampilkan data</div>
    <div class="pagination-buttons" id="paginationButtons"></div>
  </div>
</div>

<!-- ===== MODAL CATAT AKTIVITAS (MODERN UI/UX) ===== -->
<div class="modal-overlay" id="pemadamanModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Catat Aktivitas</h3>
      <button class="modal-close" onclick="closeModal()" aria-label="Tutup">&times;</button>
    </div>
    
    <form id="pemadamanForm" onsubmit="handlePemadamanSubmit(event)">
      <div class="modal-body">
        <input type="hidden" id="field_page_id" name="page_id" value="">

        <!-- Pemancar / Kategori (Selection Cards) -->
        <div class="form-group">
          <label class="form-label">Pilih Kategori / Pemancar <span class="required-star">*</span></label>
          <div class="selection-grid" id="field_pemancar">
            <?php foreach ($pemancarList as $loc): ?>
            <label class="selection-card">
              <input type="radio" name="pemancar" value="<?= htmlspecialchars($loc) ?>" required>
              <div class="card-content">
                <?php
                  $icon = 'ri-broadcast-line';
                  if (strpos($loc, 'Panel') !== false) $icon = 'ri-plug-line';
                  if (strpos($loc, 'Genset') !== false) $icon = 'ri-tools-line';
                  if (strpos($loc, 'Probolinggo') !== false) $icon = 'ri-map-pin-line';
                ?>
                <i class="<?= $icon ?>"></i>
                <span><?= htmlspecialchars($loc) ?></span>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Tanggal & Waktu (Grid Layout) -->
        <div class="form-group">
          <label class="form-label">Jadwal Aktivitas <span class="required-star">*</span></label>
          <div class="datetime-grid">
            <div>
              <input type="date" id="field_tanggal" name="tanggal" class="form-input" required>
            </div>
            <div>
              <input type="time" id="field_start_time" name="start_time" class="form-input" required>
            </div>
            <div>
              <input type="time" id="field_end_time" name="end_time" class="form-input" required>
            </div>
          </div>
        </div>

        <!-- Petugas (Chip Selector) -->
        <div class="form-group">
          <label class="form-label">
            Petugas <span class="required-star">*</span> 
            <span class="form-hint">— Pilih sebanyak yang Anda inginkan</span>
          </label>
          <div class="chip-grid" id="field_petugas">
            <?php foreach ($petugasList as $nama): ?>
            <label class="chip-option">
              <input type="checkbox" name="petugas[]" value="<?= htmlspecialchars($nama) ?>">
              <span class="chip-content"><?= htmlspecialchars($nama) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Upload Foto (Modern Drop Zone) -->
        <div class="form-group">
          <label class="form-label">Bukti Foto <span class="form-hint">(Opsional)</span></label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="files[]" id="field_files" multiple accept="image/*">
            <div class="upload-icon"><i class="ri-image-add-line"></i></div>
            <div class="upload-text">Klik atau seret file ke sini</div>
            <div class="upload-hint">Maksimal 5 MB per file, hingga 10 file (JPG, PNG)</div>
          </div>
          <div class="file-preview" id="filePreview"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="ri-send-plane-line"></i> <span id="submitBtnLabel">Kirim Data</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// ===== MODAL CONTROLS =====
function openModal(){ 
  document.getElementById('pemadamanModal').classList.add('active'); 
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}
function closeModal(){ 
  document.getElementById('pemadamanModal').classList.remove('active'); 
  document.body.style.overflow = '';
}

document.getElementById('pemadamanModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ===== FILE UPLOAD & PREVIEW LOGIC =====
let editExistingFiles = [];

function renderFilePreview() {
  const preview = document.getElementById('filePreview');
  const fileInput = document.getElementById('field_files');
  let html = '';

  if (editExistingFiles.length > 0) {
    html += `<div style="font-size:11px; font-weight:700; color:var(--text-secondary, #475569); margin-bottom:6px;">Foto Terpasang (${editExistingFiles.length}):</div>`;
    html += `<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:8px;">`;
    editExistingFiles.forEach((file, index) => {
      html += `
        <div style="position:relative; width:48px; height:48px; border-radius:6px; overflow:hidden; border:1px solid #cbd5e1;">
          <img src="${file.url}" style="width:100%; height:100%; object-fit:cover;">
          <button type="button" onclick="removeExistingFile(${index})" style="position:absolute; top:2px; right:2px; background:rgba(220, 38, 38, 0.9); color:#fff; border:none; border-radius:50%; width:16px; height:16px; font-size:10px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Hapus foto ini">×</button>
        </div>
      `;
    });
    html += `</div>`;
  }

  if (fileInput && fileInput.files && fileInput.files.length > 0) {
    const fileNames = Array.from(fileInput.files).map(f => f.name).join(', ');
    html += `<div style="font-size:12px; font-weight:600; color:#2563eb;">📎 ${fileInput.files.length} foto baru dipilih: ${fileNames}</div>`;
    document.getElementById('uploadZone').style.borderColor = '#2563eb';
    document.getElementById('uploadZone').style.background = '#eff6ff';
  } else {
    document.getElementById('uploadZone').style.borderColor = '#cbd5e1';
    document.getElementById('uploadZone').style.background = '#f8fafc';
  }

  if (html) {
    preview.innerHTML = html;
    preview.style.display = 'block';
  } else {
    preview.innerHTML = '';
    preview.style.display = 'none';
  }
}

function removeExistingFile(index) {
  editExistingFiles.splice(index, 1);
  renderFilePreview();
}

document.getElementById('field_files').addEventListener('change', renderFilePreview);

// ===== MODE CREATE =====
function openCreateModal(){
  const form = document.getElementById('pemadamanForm');
  form.reset();
  editExistingFiles = [];
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Catat Aktivitas';
  document.getElementById('submitBtnLabel').textContent = 'Kirim Data';
  renderFilePreview();
  
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('field_tanggal').value = today;
  
  const now = new Date();
  const timeStr = now.toTimeString().substring(0,5);
  document.getElementById('field_start_time').value = timeStr;
  
  openModal();
}

// ===== MODE EDIT =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('pemadamanForm');
  form.reset();
  editExistingFiles = Array.isArray(data.files) ? [...data.files] : [];

  document.getElementById('field_page_id').value = data.page_id;
  
  if (data.tanggal_raw) {
    const dateObj = new Date(data.tanggal_raw);
    if (!isNaN(dateObj.getTime())) {
      document.getElementById('field_tanggal').value = dateObj.toISOString().split('T')[0];
    }
  }

  if (data.start_time && data.start_time !== '-') {
    document.getElementById('field_start_time').value = data.start_time;
  }
  if (data.end_time && data.end_time !== '-') {
    document.getElementById('field_end_time').value = data.end_time;
  }
  
  const radio = form.querySelector(`input[name="pemancar"][value="${CSS.escape(data.pemancar)}"]`);
  if (radio) radio.checked = true;

  (data.petugas || []).forEach(nama => {
    const cb = form.querySelector(`input[name="petugas[]"][value="${CSS.escape(nama)}"]`);
    if (cb) cb.checked = true;
  });

  document.getElementById('modalTitle').textContent = 'Edit Data Aktivitas';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  renderFilePreview();
  openModal();
}

// ===== STATE PAGINATION & FILTER =====
let currentPage = 1;
const rowsPerPage = 8;
let currentFilter = 'Semua';
let currentSearchQuery = '';

function filterPemadamanTable(val) {
  currentSearchQuery = (val || '').toLowerCase().trim();
  currentPage = 1;
  updateTablePagination();
}

function filterPemadaman(value, el){
  if (el) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }
  currentFilter = value;
  currentPage = 1;
  updateTablePagination();
}

function changePage(page) {
  currentPage = page;
  updateTablePagination();
}

function updateTablePagination() {
  const allRows = Array.from(document.querySelectorAll('#pemadamanTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');

  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  const filteredRows = allRows.filter(row => {
    const rowPemancar = (row.dataset.pemancar || '').trim().toLowerCase();
    const filterVal = (currentFilter || '').trim().toLowerCase();

    let matchFilter = false;
    if (filterVal === 'semua' || !filterVal) {
      matchFilter = true;
    } else if (rowPemancar === filterVal) {
      matchFilter = true;
    } else if (rowPemancar.includes(filterVal) || filterVal.includes(rowPemancar)) {
      matchFilter = true;
    } else {
      const keywords = ['studio', 'ngijo', 'ngadirejo', 'probolinggo', 'auditorium', 'panel', 'genset'];
      for (const kw of keywords) {
        if (filterVal.includes(kw) && rowPemancar.includes(kw)) {
          matchFilter = true;
          break;
        }
      }
    }

    const text = row.innerText.toLowerCase();
    const matchSearch = !currentSearchQuery || text.includes(currentSearchQuery.toLowerCase());
    return matchFilter && matchSearch;
  });

  const totalRows = filteredRows.length;
  const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  allRows.forEach(row => row.style.display = 'none');

  const startIndex = (currentPage - 1) * rowsPerPage;
  const endIndex = Math.min(startIndex + rowsPerPage, totalRows);
  for (let i = startIndex; i < endIndex; i++) {
    filteredRows[i].style.display = '';
  }

  const paginationInfo = document.getElementById('paginationInfo');
  const paginationButtons = document.getElementById('paginationButtons');

  if (totalRows === 0 || !paginationWrap) {
    if (paginationWrap) paginationWrap.style.display = 'none';
  } else {
    paginationWrap.style.display = 'flex';
    paginationWrap.style.alignItems = 'center';
    paginationWrap.style.justifyContent = 'space-between';
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} data`;

    paginationButtons.style.display = 'flex';
    paginationButtons.style.alignItems = 'center';
    paginationButtons.style.gap = '0.5rem';

    let buttonsHTML = '';
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const prevStyle = currentPage === 1 ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" class="btn-page" style="${prevStyle}" onclick="changePage(${currentPage - 1})" ${prevDisabled}>&laquo; Prev</button>`;

    buttonsHTML += `<input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="${currentPage}" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="jumpToPage(this.value, ${totalPages})" onkeydown="if(event.key === 'Enter') jumpToPage(this.value, ${totalPages})">`;

    buttonsHTML += `<span style="font-size:0.9rem; color:#475569; display:inline-flex; align-items:center;">dari ${totalPages}</span>`;

    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextStyle = currentPage === totalPages ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" class="btn-page" style="${nextStyle}" onclick="changePage(${currentPage + 1})" ${nextDisabled}>Next &raquo;</button>`;
    paginationButtons.innerHTML = buttonsHTML;
  }
}

function jumpToPage(val, totalPages) {
  let page = parseInt(val, 10);
  if (isNaN(page)) { updateTablePagination(); return; }
  if (page < 1) page = 1;
  if (page > totalPages) page = totalPages;
  changePage(page);
}

document.addEventListener('DOMContentLoaded', () => {
  updateTablePagination();
});

// ===== SUBMIT (CREATE/UPDATE) =====
async function handlePemadamanSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;

  const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
  if (petugasChecked.length === 0) { showToast('⚠️ Pilih minimal satu Petugas!', 'error'); return; }

  const pemancar = form.querySelector('input[name="pemancar"]:checked')?.value;
  if (!pemancar) { showToast('⚠️ Pilih Kategori/Pemancar!', 'error'); return; }

  submitBtn.disabled = true;
  submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyimpan...';

  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  try {
    let uploadedFiles = [];
    const fileInput = document.getElementById('field_files');
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
      submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mengunggah Foto...';
      const formData = new FormData();
      for (const file of fileInput.files) {
        formData.append('files[]', file);
      }
      const uploadRes = await fetch('../api/upload-to-notion.php', {
        method: 'POST',
        body: formData
      });
      const uploadData = await uploadRes.json();
      if (uploadData.success && Array.isArray(uploadData.files)) {
        uploadedFiles = uploadData.files;
      }
    }

    const payload = {
      pemancar: pemancar,
      tanggal: form.tanggal.value,
      start_time: form.start_time.value,
      end_time: form.end_time.value,
      petugas: petugasChecked,
      files: uploadedFiles,
      existing_files: editExistingFiles
    };
    if (isEdit) payload.page_id = pageId;

    const endpoint = isEdit ? '../api/update-pemadaman.php' : '../api/create-pemadaman.php';
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message, 'success');
      closeModal();
      form.reset();
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    showToast('❌ ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtnLabel.innerHTML = `<i class="ri-send-plane-line"></i> ${originalLabel}`;
  }
}

async function deletePemadaman(pageId, label) {
  customConfirm(
    'Hapus Catatan Listrik',
    `Hapus catatan "${label}"? Data akan dipindahkan ke Trash di Notion.`,
    async () => {
      try {
        const res = await fetch('../api/delete-pemadaman.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ page_id: pageId })
        });
        const data = await res.json();
        if (data.success) {
          showToast('✅ ' + data.message, 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
        }
      } catch (err) {
        showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
      }
    },
    'Hapus'
  );
}

// showToast() and openLightbox() are provided globally by /includes/footer.php and /assets/css/js/sonner.js
</script>

<script>
window.__tourKey   = 'tour_pemadaman_v1';
window.__tourSteps = [
  { target: '.page-head',     title: 'Logbook Aktivitas Listrik', body: 'Halaman ini untuk mencatat dan memantau semua aktivitas kelistrikan: pemadaman PLN, panel, dan maintenance genset.' },
  { target: '.stats-row',     title: 'Ringkasan Aktivitas',       body: 'Lihat total log, jumlah pemadaman PLN, aktivitas panel auditorium, dan maintenance genset yang tercatat.' },
  { target: '.filter-tabs',   title: 'Filter Kategori',           body: 'Saring log berdasarkan jenis pemancar atau kategori aktivitas kelistrikan yang ingin ditampilkan.' },
  { target: '.btn.btn-primary', title: 'Catat Aktivitas Baru',   body: 'Klik tombol ini untuk membuka form pencatatan aktivitas kelistrikan baru — pemadaman, panel, atau genset.' },
];
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>