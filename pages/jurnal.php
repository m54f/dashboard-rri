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

<style>
/* Hilangkan tombol panah atas-bawah (spinner) pada input halaman pagination */
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
  .table-controls {
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 10px !important;
  }
  .table-controls #searchInput {
    width: 100% !important;
  }
  .filter-tabs {
    display: flex !important;
    overflow-x: auto !important;
    white-space: nowrap !important;
    flex-wrap: nowrap !important;
    padding-bottom: 8px !important;
    margin-bottom: 0.5rem !important;
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
  #jurnalTable {
    min-width: 720px !important;
  }
  #jurnalTable th, #jurnalTable td {
    padding: 0.75rem 0.6rem !important;
    font-size: 0.83rem !important;
  }
  .pagination-wrap {
    flex-direction: column;
    align-items: stretch !important;
    text-align: center;
  }
}
</style>

<div class="page-head">
  <div>
    <h2>Logbook Jurnal Kegiatan</h2>
    <p style="color: #6b7280; margin-top: 5px;">Dokumentasi kegiatan operasional tim TMB.</p>
  </div>
  <div class="page-head-actions">
    <div class="search-box page-head-search">
      <i class="ri-search-line"></i>
      <input type="text" id="searchInput" placeholder="Cari kegiatan/petugas..." onkeyup="handleSearch(this.value)" style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Tulis Jurnal</button>
  </div>
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
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="ri-list-check"></i> Daftar Kegiatan</div></div>
  <div class="table-wrap">
    <table class="data-table" id="jurnalTable">
      <thead>
        <tr><th>JUDUL</th><th>KATEGORI</th><th>PIC</th><th>DOKUMENTASI</th><th>LINK EVIDEN</th><th>TANGGAL</th><th>AKSI</th></tr>
      </thead>
      <tbody id="tableBody"></tbody>
    </table>
  </div>
  
  <div class="pagination-wrap">
    <div id="pageInfo" style="font-size:0.85rem; color:var(--text-secondary); font-weight:500;">Menampilkan 0-0 dari 0 Kegiatan</div>
    <div id="paginationNav" style="display:flex; align-items:center; gap:0.35rem;"></div>
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
            <div style="font-size: 0.9rem; color: var(--text-primary); font-weight: 600;">Klik atau seret foto ke sini</div>
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

function htmlEscape(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function renderTable() {
    let temp = allEntries;
    if (currentFilter !== 'semua') {
        temp = temp.filter(item => (item.status || '').toLowerCase() === currentFilter.toLowerCase());
    }
    if (searchQuery.trim() !== '') {
        let q = searchQuery.toLowerCase();
        temp = temp.filter(item => (item.title || '').toLowerCase().includes(q) || (item.op || '').toLowerCase().includes(q) || (item.tag || '').toLowerCase().includes(q));
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
            let limit = 3;
            let publicUrl = e.url || '#';
            
            let imageDocs = (e.docs || []).filter(d => {
                const urlLower = (d.url || '').toLowerCase();
                const nameLower = (d.name || '').toLowerCase();
                return urlLower.match(/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i) ||
                       nameLower.match(/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i) ||
                       (!nameLower.match(/\.(pdf|docx?|xlsx?|zip|rar|txt|csv)$/i) && !urlLower.match(/\.(pdf|docx?|xlsx?|zip|rar|txt|csv)(\?.*)?$/i));
            });
            let nonImageDocs = (e.docs || []).filter(d => !imageDocs.includes(d));

            if (imageDocs.length > 0) {
                let docImagesUrls = imageDocs.map(d => d.url);
                let jsonImageUrls = htmlEscape(JSON.stringify(docImagesUrls));
                
                docsHtml += `<div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">`;
                for (let i = 0; i < Math.min(imageDocs.length, limit); i++) {
                    let proxyThumb = '../api/image-proxy.php?url=' + encodeURIComponent(imageDocs[i].url) + '&w=150';
                    docsHtml += `<img src="${proxyThumb}" loading="lazy" class="doc-thumb" title="${htmlEscape(imageDocs[i].name || 'Foto')}" onclick='openLightbox(${jsonImageUrls}, ${i})' onerror="this.src='${imageDocs[i].url}'" style="width:40px; height:40px; object-fit:cover; border-radius:8px; cursor:pointer; border:1px solid var(--border-color, #e2e8f0); transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">`;
                }
                if (imageDocs.length > limit) {
                    docsHtml += `<span style="font-size:12px; font-weight:700; color:var(--text-secondary, #475569); cursor:pointer; align-self:center;" onclick='openLightbox(${jsonImageUrls}, ${limit})'>+${imageDocs.length - limit}</span>`;
                }
                docsHtml += `</div>`;
            }

            if (nonImageDocs.length > 0) {
                docsHtml += `<div style="display:flex; gap:4px; flex-wrap:wrap; margin-top:${imageDocs.length > 0 ? '4px' : '0'};">`;
                nonImageDocs.forEach(doc => {
                    let ext = (doc.name || 'DOC').split('.').pop().toUpperCase();
                    docsHtml += `<a href="${doc.url}" target="_blank" class="doc-more" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; font-size:11px; text-decoration:none; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;" title="${htmlEscape(doc.name || 'Dokumen')}"><i class="ri-file-text-line"></i> ${ext}</a>`;
                });
                docsHtml += `</div>`;
            }

            if (e.docs.length === 0) docsHtml = `<span style="color:#9ca3af;font-size:12px;">-</span>`;

            let rawEviden = (e.eviden_3 || '').trim();
            if (rawEviden && !rawEviden.match(/^https?:\/\//i)) {
                rawEviden = 'https://' + rawEviden;
            }
            let linkHtml = rawEviden ? `<a href="${rawEviden}" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:600;"><i class="ri-link"></i> Buka</a>` : `<span style="color:#9ca3af;font-size:12px;">-</span>`;

            let editPayload = encodeURIComponent(JSON.stringify({
                page_id: e.full_id, nama: e.title, tanggal: e.date_raw, eviden_3: e.eviden_3,
                jenis: e.tags_arr, petugas: e.ops_arr, docs: e.docs
            }));

            let actionButtons = `<a href="${publicUrl}" target="_blank" class="icon-action view" title="Buka di Notion"><i class="ri-external-link-line"></i></a>`;
            
            // Only show edit/delete buttons for admin role
            if (userRole === 'admin') {
                actionButtons += `
                    <button class="icon-action edit" title="Edit" onclick="openEditModal(decodeURIComponent('${editPayload}'))"><i class="ri-pencil-line"></i></button>
                    <button class="icon-action delete" title="Hapus" onclick="deleteJurnal('${e.full_id}', '${e.title.replace(/'/g, "\\'")}')"><i class="ri-delete-bin-line"></i></button>
                `;
            }

            let picInitials = e.op ? e.op.substring(0, 2).toUpperCase() : 'OP';

            tbody.innerHTML += `
            <tr>
                <td class="cell-title">${e.title}</td>
                <td><span class="tag blue">${e.tag}</span></td>
                <td><div style="display:flex; align-items:center; gap:8px;"><span>${e.op}</span></div></td>
                <td><div class="doc-thumbnails">${docsHtml}</div></td>
                <td>${linkHtml}</td>
                <td style="font-weight:600; white-space:nowrap;">${e.date}</td>
                <td class="cell-actions">
                    ${actionButtons}
                </td>
            </tr>`;
        });
    }

    let startInfo = totalItems === 0 ? 0 : startIndex + 1;
    let endInfo = Math.min(endIndex, totalItems);
    document.getElementById('pageInfo').innerText = `Menampilkan ${startInfo}-${endInfo} dari ${totalItems} Kegiatan`;
    renderPagination(totalPages);
}

// FUNGSI PAGINATION TINGKAT LANJUT (Desain Input Minimalis Fokus Ketik Angka)
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
            <input type="text"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   class="page-item page-input-clean" 
                   style="width: 50px; padding: 6px; text-align: center; margin: 0; outline: none; box-sizing: border-box; font-weight: 600;" 
                   value="${currentPage}" 
                   onfocus="this.select()"
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
    customConfirm(
      'Hapus Jurnal',
      `Hapus jurnal "${title}"? Data akan diarsipkan di Notion.`,
      async () => {
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
      },
      'Hapus'
    );
}

// showToast() is provided globally by /assets/css/js/sonner.js

document.addEventListener("DOMContentLoaded", () => { renderTable(); });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>