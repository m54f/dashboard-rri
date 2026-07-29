<?php
$base = '..'; 
$pageTitle = 'Jurnal Kegiatan'; 
$activeMenu = 'jurnal';

// Panggil fungsi API
require_once __DIR__ . '/../api/jurnal.php';
$jurnalData = getJurnal();

// Keamanan: Pastikan data adalah array
if (!is_array($jurnalData) || isset($jurnalData['error'])) {
    $errorMessage = $jurnalData['error'] ?? 'Gagal memuat data';
    $jurnalData = [];
}

$petugasList = ['Dicky','Priyo','Ludfi','Anggi','Deny','Yhanuar','Shofirin','Yudha','Dayat','Dedy','Winsa','Yayang','Syukur','Saiful','Alif','Selvy','Alfian'];
$jenisKegiatanList = ['Acara Live / Dialog Luar','Podcast / Tapping','Troubleshooting / Maintenance','Supporting Event Kantor','Keroncong / Senandung Rindu','Zoom Meeting','Kunjungan','Programa','Editing Audio / Image / Video','Lain-Lain'];

// WAJIB MEMANGGIL HEADER DI SINI AGAR SIDEBAR & TOPBAR AKTIF
include __DIR__ . '/../includes/header.php';
?>

<!-- ===== STYLE MODERN MODAL & UI ===== -->
<style>
.page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
.filter-tabs { display: flex; gap: 10px; }
.filter-tab { padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
.filter-tab.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.search-box { position: relative; }
.search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
.search-box input { padding: 10px 14px 10px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; width: 280px; transition: 0.2s; }
.search-box input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

.table-card { background: #fff; border-radius: 12px; border: 1px solid #f3f4f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;}
.table-header { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 18px; margin-bottom: 25px; color: #111827; }
.styled-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
.styled-table th { padding: 12px 15px; color: #6b7280; font-weight: 700; font-size: 12px; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; }
.styled-table td { padding: 16px 15px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
.styled-table tbody tr:hover { background-color: #f8fafc; }

.item-title { font-weight: 700; color: #111827; max-width: 250px; line-height: 1.4; }
.badge-kategori { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.doc-thumbnails { display: flex; flex-wrap: wrap; gap: 4px; }
.doc-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; background: #e5e7eb; border: 1px solid #f3f4f6; cursor: pointer; transition: 0.2s; }
.doc-thumb:hover { opacity: 0.8; }
.doc-more { width: 36px; height: 36px; border-radius: 6px; background: #f9fafb; border: 1px solid #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; cursor: pointer; }

.action-btns { display: flex; gap: 8px; }
.btn-icon { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; color: #9ca3af; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: 0.2s; }
.btn-icon.edit { color: #2563eb; } .btn-icon.edit:hover { background: #eff6ff; border-color: #bfdbfe; }
.btn-icon.delete { color: #dc2626; } .btn-icon.delete:hover { background: #fef2f2; border-color: #fecaca; }

/* === STYLING PAGINATION RESPONSIVE === */
.pagination-container { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-top: 25px; 
  font-size: 14px; 
  color: #6b7280; 
  padding-top: 20px; 
  border-top: 1px solid #f3f4f6; 
  gap: 15px; 
}
.page-item { 
  padding: 6px 12px; 
  border: 1px solid #e5e7eb; 
  border-radius: 6px; 
  background: #fff; 
  color: #374151; 
  cursor: pointer; 
  font-weight: 500; 
  transition: 0.2s; 
  font-size: 0.9rem;
}
.page-item:disabled { opacity: 0.5; cursor: not-allowed; }

/* Khusus Mobile Layout Pagination */
@media screen and (max-width: 768px) {
  .pagination-container {
    flex-direction: column;
    justify-content: center;
    text-align: center;
  }
}

/* === STYLING MODAL SCROLLABLE === */
.modal-overlay { 
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); 
  display: flex; align-items: center; justify-content: center; z-index: 9999; 
  opacity: 0; visibility: hidden; transition: all 0.3s ease; padding: 1rem; 
}
.modal-overlay.active { opacity: 1; visibility: visible; }

.modal-content { 
  background: #ffffff; border-radius: 16px; width: 100%; max-width: 680px; 
  max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; 
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
  transform: translateY(20px) scale(0.95); transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
}
.modal-overlay.active .modal-content { transform: translateY(0) scale(1); }

#jurnalForm { display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; }

.modal-header { 
  display: flex; justify-content: space-between; align-items: center; 
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; background: #ffffff; flex-shrink: 0; 
}
.modal-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.modal-close { background: #f1f5f9; border: none; width: 34px; height: 34px; border-radius: 8px; font-size: 1.5rem; line-height: 1; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.modal-close:hover { background: #e2e8f0; color: #0f172a; }

.modal-body { padding: 1.5rem; flex: 1 1 auto; min-height: 0; overflow-y: auto; background: #ffffff; }
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.modal-footer { 
  display: flex; justify-content: flex-end; gap: 0.75rem; 
  padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; background: #ffffff; flex-shrink: 0; 
}

.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
.form-input { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; color: #0f172a; background: #fff; outline: none; font-family: inherit; box-sizing: border-box;}
.form-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

.chip-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip-option { cursor: pointer; user-select: none; position: relative; }
.chip-option input[type="checkbox"] { position: absolute; opacity: 0; }
.chip-content { display: inline-block; padding: 0.5rem 1rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 0.85rem; font-weight: 600; border: 1.5px solid transparent; transition: all 0.2s ease; }
.chip-option input[type="checkbox"]:checked + .chip-content { background: #2563eb; color: #fff; border-color: #1d4ed8; }

.upload-zone { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; text-align: center; background: #f8fafc; cursor: pointer; position: relative; }
.preview-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.preview-item { position: relative; width: 80px; height: 80px; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; }
.preview-item img { width: 100%; height: 100%; object-fit: cover; }
.preview-item .remove-btn { position: absolute; top: 2px; right: 2px; width: 20px; height: 20px; background: rgba(239,68,68,0.9); color: #fff; border: none; border-radius: 50%; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }

.btn { padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 0.5rem; }
.btn-secondary { background: #fff; color: #475569; border: 1.5px solid #cbd5e1; }
.btn-primary { background: #2563eb; color: #fff; }
</style>

<div class="page-head">
  <div>
    <h2>Logbook Jurnal Kegiatan</h2>
    <p style="color: #6b7280; margin-top: 5px;">Dokumentasi kegiatan operasional tim TMB.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Tulis Jurnal</button>
</div>

<?php if (isset($errorMessage)): ?>
  <div style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="table-controls">
    <div class="filter-tabs">
        <button class="filter-tab active" onclick="setFilter('semua', this)">Semua</button>
        <button class="filter-tab" onclick="setFilter('belum selesai', this)">Belum Selesai</button>
        <button class="filter-tab" onclick="setFilter('proses', this)">Proses</button>
        <button class="filter-tab" onclick="setFilter('selesai', this)">Selesai</button>
    </div>
    <div class="search-box">
        <i class="ri-search-line"></i>
        <input type="text" id="searchInput" placeholder="Cari kegiatan atau petugas..." onkeyup="handleSearch(this.value)">
    </div>
</div>

<div class="table-card">
  <div class="table-header"><i class="ri-list-check" style="color:#2563eb;"></i> Daftar Kegiatan</div>
  <div style="overflow-x: auto;">
    <table class="styled-table">
      <thead>
        <tr><th>JUDUL</th><th>KATEGORI</th><th>PIC</th><th>DOKUMENTASI</th><th>LINK EVIDEN</th><th>TANGGAL</th><th>AKSI</th></tr>
      </thead>
      <tbody id="tableBody"></tbody>
    </table>
  </div>
  
  <div class="pagination-container">
    <div id="pageInfo">Menampilkan 0-0 dari 0 Kegiatan</div>
    <div id="paginationNav"></div>
  </div>
</div>

<!-- ===== MODAL FORM ===== -->
<div class="modal-overlay" id="jurnalModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Formulir Jurnal Kegiatan</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    
    <form id="jurnalForm" onsubmit="handleJurnalSubmit(event)">
      <div class="modal-body">
        <input type="hidden" id="field_page_id" name="page_id" value="">

        <div class="form-group">
          <label class="form-label">Nama Kegiatan <span style="color:#ef4444">*</span></label>
          <input type="text" id="field_nama" name="nama_kegiatan" class="form-input" placeholder="Contoh: Maintenance Pemancar" required>
        </div>

        <div class="form-group">
          <label class="form-label">Jenis Kegiatan <span style="color:#ef4444">*</span></label>
          <div class="chip-grid" id="field_jenis">
            <?php foreach ($jenisKegiatanList as $jenis): ?>
            <label class="chip-option">
              <input type="checkbox" name="jenis_kegiatan[]" value="<?= htmlspecialchars($jenis) ?>">
              <span class="chip-content"><?= htmlspecialchars($jenis) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Tanggal & Waktu <span style="color:#ef4444">*</span></label>
          <input type="datetime-local" id="field_tanggal" name="tanggal" class="form-input" required>
        </div>

        <div class="form-group">
          <label class="form-label">Petugas <span style="color:#ef4444">*</span></label>
          <div class="chip-grid" id="field_petugas">
            <?php foreach ($petugasList as $nama): ?>
            <label class="chip-option">
              <input type="checkbox" name="petugas[]" value="<?= htmlspecialchars($nama) ?>">
              <span class="chip-content"><?= htmlspecialchars($nama) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Dokumentasi (Foto)</label>
          <div class="upload-zone" id="dropzone">
            <input type="file" id="field_eviden" name="eviden[]" multiple accept="image/*">
            <div style="font-size: 2rem; color: #64748b; margin-bottom: 0.5rem;"><i class="ri-image-add-line"></i></div>
            <div style="font-size: 0.9rem; color: #334155; font-weight: 600;">Klik atau seret foto ke sini</div>
          </div>
          <div class="preview-grid" id="filePreview" style="display: none;"></div>
          <div class="preview-grid" id="existingDocs" style="display: none;"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Link Eviden Tambahan (Opsional)</label>
          <input type="url" id="field_eviden_3" name="eviden_3_link" class="form-input" placeholder="https://...">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="ri-send-plane-line"></i> <span id="submitBtnLabel">Simpan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Lightbox -->
<div id="imageLightbox" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:99999; flex-direction:column; justify-content:center; align-items:center; backdrop-filter:blur(5px);" onclick="this.style.display='none'">
    <span style="position:absolute; top:20px; right:40px; font-size:40px; color:white; cursor:pointer;">&times;</span>
    <img id="lightboxImg" src="" style="max-width:90%; max-height:80vh; border-radius:8px; object-fit:contain;">
</div>

<script>
const allEntries = <?= json_encode($jurnalData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const userRole = '<?= getRole() ?>'; // Get current user role
let filteredEntries = [...allEntries];
let currentPage = 1;
const itemsPerPage = 10; 
let currentFilter = 'semua';
let searchQuery = '';
let selectedFiles = [];
let existingDocs = [];

function renderTable() {
    let temp = allEntries;
    if (currentFilter !== 'semua') {
        temp = temp.filter(item => item.status.toLowerCase() === currentFilter.toLowerCase());
    }
    if (searchQuery.trim() !== '') {
        let q = searchQuery.toLowerCase();
        temp = temp.filter(item => item.title.toLowerCase().includes(q) || item.op.toLowerCase().includes(q) || item.tag.toLowerCase().includes(q));
    }
    
    filteredEntries = temp;
    let totalItems = filteredEntries.length;
    let totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    let startIndex = (currentPage - 1) * itemsPerPage;
    let endIndex = startIndex + itemsPerPage;
    let pageData = filteredEntries.slice(startIndex, endIndex);

    let tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    if (pageData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 40px; color: #6b7280;">Tidak ada data ditemukan.</td></tr>`;
    } else {
        pageData.forEach(e => {
            let docsHtml = '';
            let limit = 1;
            let publicUrl = e.url.replace('https://www.notion.so/', 'https://tmbrrimlg.notion.site/').replace('https://app.notion.com/', 'https://tmbrrimlg.notion.site/');
            
            for(let i=0; i < Math.min(e.docs.length, limit); i++) {
                docsHtml += `<img src="${e.docs[i].url}" class="doc-thumb" onclick="openLightbox('${e.docs[i].url}')" onerror="this.src='https://via.placeholder.com/36?text=IMG'">`;
            }
            if(e.docs.length > limit) {
                docsHtml += `<div class="doc-more" onclick="window.open('${publicUrl}', '_blank')">+${e.docs.length - limit}</div>`;
            }
            if(e.docs.length === 0) docsHtml = `<span style="color:#9ca3af;font-size:12px;">-</span>`;

            let linkHtml = e.eviden_3 ? `<a href="${e.eviden_3}" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:600;"><i class="ri-link"></i> Buka</a>` : `<span style="color:#9ca3af;font-size:12px;">-</span>`;

            let editPayload = encodeURIComponent(JSON.stringify({
                page_id: e.full_id, nama: e.title, tanggal: e.date_raw, eviden_3: e.eviden_3,
                jenis: e.tags_arr, petugas: e.ops_arr, docs: e.docs
            }));

            let actionButtons = `<a href="${publicUrl}" target="_blank" class="btn-icon" title="Buka di Notion"><i class="ri-external-link-line"></i></a>`;
            
            // Only show edit/delete buttons for admin role
            if (userRole === 'admin') {
                actionButtons += `
                    <button class="btn-icon edit" title="Edit" onclick="openEditModal(decodeURIComponent('${editPayload}'))"><i class="ri-pencil-line"></i></button>
                    <button class="btn-icon delete" title="Hapus" onclick="deleteJurnal('${e.full_id}', '${e.title.replace(/'/g, "\\'")}')"><i class="ri-delete-bin-line"></i></button>
                `;
            }

            tbody.innerHTML += `
            <tr>
                <td class="item-title">${e.title}</td>
                <td><span class="badge-kategori">${e.tag}</span></td>
                <td style="max-width: 150px; line-height: 1.5;">${e.op}</td>
                <td><div class="doc-thumbnails">${docsHtml}</div></td>
                <td>${linkHtml}</td>
                <td>${e.date}</td>
                <td>
                    <div class="action-btns">${actionButtons}</div>
                </td>
            </tr>`;
        });
    }

    let startInfo = totalItems === 0 ? 0 : startIndex + 1;
    let endInfo = Math.min(endIndex, totalItems);
    document.getElementById('pageInfo').innerText = `Menampilkan ${startInfo}-${endInfo} dari ${totalItems} Kegiatan`;
    renderPagination(totalPages);
}

// FUNGSI PAGINATION TINGKAT LANJUT (Desain Input Minimalis)
function renderPagination(totalPages) {
    let pg = document.getElementById('paginationNav');
    pg.innerHTML = '';

    pg.style.display = 'flex';
    pg.style.alignItems = 'center';
    pg.style.justifyContent = 'center';
    pg.style.gap = '8px';
    pg.style.flexWrap = 'wrap'; 

    pg.innerHTML += `<button class="page-item" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Prev</button>`;

    pg.innerHTML += `
        <div style="display: flex; align-items: center; gap: 8px;">
            <input type="number" 
                   class="page-item" 
                   style="width: 55px; padding: 6px; text-align: center; margin: 0; outline: none; box-sizing: border-box;" 
                   value="${currentPage}" 
                   min="1" max="${totalPages}"
                   onchange="jumpToPage(this.value, ${totalPages})"
                   onkeyup="if(event.key === 'Enter') jumpToPage(this.value, ${totalPages})"
                   title="Ketik nomor halaman lalu tekan Enter">
            <span style="font-size: 13px; color: #6b7280; font-weight: 500;">dari ${totalPages}</span>
        </div>
    `;

    pg.innerHTML += `<button class="page-item" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>Next &raquo;</button>`;
}

function jumpToPage(val, totalPages) {
    let page = parseInt(val);
    if (isNaN(page)) {
        renderPagination(totalPages); 
        return;
    }
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    
    if (page !== currentPage) {
        changePage(page);
    } else {
        renderPagination(totalPages);
    }
}

function changePage(page) {
    let totalPages = Math.ceil(filteredEntries.length / itemsPerPage) || 1;
    if (page >= 1 && page <= totalPages) { currentPage = page; renderTable(); }
}

function setFilter(status, btn) {
    currentFilter = status; currentPage = 1;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active'); renderTable();
}

function handleSearch(val) { searchQuery = val; currentPage = 1; renderTable(); }
function openLightbox(url) { document.getElementById('lightboxImg').src = url; document.getElementById('imageLightbox').style.display = 'flex'; }

function openModal(){ document.getElementById('jurnalModal').classList.add('active'); document.body.style.overflow = 'hidden'; }
function closeModal(){ document.getElementById('jurnalModal').classList.remove('active'); document.body.style.overflow = ''; }
document.getElementById('jurnalModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function resetForm() {
    document.getElementById('jurnalForm').reset();
    document.getElementById('field_page_id').value = '';
    selectedFiles = []; existingDocs = [];
    document.getElementById('filePreview').innerHTML = ''; document.getElementById('filePreview').style.display = 'none';
    document.getElementById('existingDocs').innerHTML = ''; document.getElementById('existingDocs').style.display = 'none';
}

function openCreateModal() {
    resetForm();
    document.getElementById('modalTitle').innerText = "Formulir Jurnal Kegiatan";
    document.getElementById('submitBtnLabel').innerText = "Simpan";
    openModal();
}

function openEditModal(payloadStr) {
    resetForm();
    let data = JSON.parse(payloadStr);
    document.getElementById('modalTitle').innerText = "Edit Jurnal Kegiatan";
    document.getElementById('submitBtnLabel').innerText = "Simpan Perubahan";
    document.getElementById('field_page_id').value = data.page_id;
    document.getElementById('field_nama').value = data.nama;
    document.getElementById('field_tanggal').value = data.tanggal;
    document.getElementById('field_eviden_3').value = data.eviden_3 || '';

    (data.jenis || []).forEach(val => {
        let cb = document.querySelector(`input[name="jenis_kegiatan[]"][value="${CSS.escape(val)}"]`);
        if(cb) cb.checked = true;
    });
    (data.petugas || []).forEach(val => {
        let cb = document.querySelector(`input[name="petugas[]"][value="${CSS.escape(val)}"]`);
        if(cb) cb.checked = true;
    });

    existingDocs = data.docs || [];
    renderExistingDocs(existingDocs);
    openModal();
}

const dropzone = document.getElementById('dropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.classList.remove('dragover'); handleFileSelect(e.dataTransfer.files); });
document.getElementById('field_eviden').addEventListener('change', function() { handleFileSelect(this.files); });

function handleFileSelect(files) {
    for (const file of files) {
        if (!file.type.startsWith('image/')) continue;
        if (file.size > 10 * 1024 * 1024) { showToast('⚠️ ' + file.name + ' melebihi 10MB', 'error'); continue; }
        selectedFiles.push(file);
    }
    renderPreviews();
}

function renderPreviews() {
    const grid = document.getElementById('filePreview');
    grid.innerHTML = '';
    if (selectedFiles.length > 0) {
        grid.style.display = 'flex';
        selectedFiles.forEach((file, idx) => {
            const div = document.createElement('div'); div.className = 'preview-item';
            const img = document.createElement('img'); img.src = URL.createObjectURL(file);
            const btn = document.createElement('button'); btn.className = 'remove-btn'; btn.innerHTML = '&times;';
            btn.onclick = (e) => { e.stopPropagation(); selectedFiles.splice(idx, 1); renderPreviews(); };
            div.appendChild(img); div.appendChild(btn); grid.appendChild(div);
        });
    } else { grid.style.display = 'none'; }
}

function renderExistingDocs(docs) {
    const grid = document.getElementById('existingDocs'); grid.innerHTML = '';
    if (docs && docs.length > 0) {
        grid.style.display = 'flex';
        docs.forEach((doc, idx) => {
            const div = document.createElement('div'); div.className = 'preview-item';
            const img = document.createElement('img'); img.src = doc.url;
            const badge = document.createElement('span'); badge.style.cssText = 'position:absolute;bottom:2px;left:2px;background:rgba(37,99,235,0.85);color:#fff;font-size:8px;padding:1px 4px;border-radius:4px;'; badge.textContent = 'EXISTING';
            const btn = document.createElement('button'); btn.className = 'remove-btn'; btn.innerHTML = '&times;';
            btn.onclick = (e) => { e.stopPropagation(); existingDocs.splice(idx, 1); renderExistingDocs(existingDocs); };
            div.appendChild(img); div.appendChild(badge); div.appendChild(btn); grid.appendChild(div);
        });
    } else { grid.style.display = 'none'; }
}

async function uploadFiles(files) {
    if (files.length === 0) return [];
    const formData = new FormData();
    files.forEach(f => formData.append('eviden[]', f)); 
    
    const res = await fetch('../api/upload-to-notion.php', { method: 'POST', body: formData });
    if (!res.headers.get("content-type")?.includes("application/json")) {
        throw new Error("Gagal upload: Server error (Cek Console F12)");
    }
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Upload ke Notion gagal');
    return data.files;
}

async function handleJurnalSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnLabel = document.getElementById('submitBtnLabel');
    const originalLabel = submitBtnLabel.innerText;

    const jenisChecked = [...form.querySelectorAll('input[name="jenis_kegiatan[]"]:checked')].map(cb => cb.value);
    const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
    
    if (jenisChecked.length === 0) { showToast('⚠️ Pilih minimal satu Jenis Kegiatan!', 'error'); return; }
    if (petugasChecked.length === 0) { showToast('⚠️ Pilih minimal satu Nama Petugas!', 'error'); return; }

    submitBtn.disabled = true;
    submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Memproses...';

    const pageId = document.getElementById('field_page_id').value;
    const isEdit = !!pageId;

    try {
        let uploadedFiles = [];
        if (selectedFiles.length > 0) {
            submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Upload Foto...';
            uploadedFiles = await uploadFiles(selectedFiles);
        }

        submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyimpan...';

        const payload = {
            nama_kegiatan: form.nama_kegiatan.value.trim(),
            tanggal: form.tanggal.value,
            jenis_kegiatan: jenisChecked,
            petugas: petugasChecked,
            eviden_3_link: form.eviden_3_link.value.trim(),
            dokumentasi_file_ids: uploadedFiles,
            dokumentasi_keep: existingDocs
        };
        if (isEdit) payload.page_id = pageId;

        const endpoint = isEdit ? '../api/update-jurnal.php' : '../api/create-jurnal.php';
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const contentType = res.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            const data = await res.json();
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                closeModal();
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
            }
        } else {
            const errorText = await res.text();
            console.error("Server error:", errorText);
            showToast('❌ Error Server: Cek Console (F12).', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        showToast('❌ ' + err.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtnLabel.innerText = originalLabel;
    }
}

async function deleteJurnal(pageId, title) {
    if (!confirm(`Hapus jurnal "${title}"?\n\nData akan diarsipkan di Notion.`)) return;
    try {
        const res = await fetch('../api/delete-jurnal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ page_id: pageId })
        });
        
        const contentType = res.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            const data = await res.json();
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
            }
        } else {
            showToast('❌ Error Server: Cek Console (F12).', 'error');
        }
    } catch (err) {
        showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
    }
}

function showToast(message, type) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'toast-notification ' + type;
    toast.textContent = message;
    toast.style.cssText = `position: fixed; bottom: 2rem; right: 2rem; z-index: 99999; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 600; font-size: 0.95rem; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.18); ${type === 'success' ? 'background: #22c55e;' : 'background: #ef4444;'}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 4000);
}

document.addEventListener("DOMContentLoaded", () => { renderTable(); });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>