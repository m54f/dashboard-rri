<?php
$base = '..'; 
$pageTitle = 'Jurnal Kegiatan'; 
$activeMenu = 'jurnal';

// Panggil fungsi API yang baru saja dibuat
require_once __DIR__ . '/../api/jurnal.php';
$jurnalData = getJurnal();

// Cek apakah API mereturn error (agar tidak merusak array JS)
if (isset($jurnalData['error'])) {
    $jurnalData = []; // Kosongkan array jika terjadi error
    $api_error = true;
} else {
    $api_error = false;
}

// Tangkap Notifikasi dari proses Create, Update, Delete
if (isset($_GET['status'])) {
    $msg = '';
    if ($_GET['status'] == 'created') $msg = 'Jurnal berhasil ditambahkan!';
    if ($_GET['status'] == 'updated') $msg = 'Jurnal berhasil diperbarui!';
    if ($_GET['status'] == 'deleted') $msg = 'Jurnal berhasil dihapus!';
    if ($_GET['status'] == 'error') $msg = 'Terjadi kesalahan pada sistem!';
    if ($msg) echo "<script>alert('$msg'); window.history.replaceState(null, null, window.location.pathname);</script>";
}

include __DIR__ . '/../includes/header.php';
?>

<!-- STYLING CSS -->
<style>
  .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
  .table-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
  .filter-tabs { display: flex; gap: 10px; }
  .btn-filter { padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; color: #6b7280; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
  .btn-filter.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
  .search-box { position: relative; }
  .search-box i { position: absolute; left: 28px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
  .search-box input { padding: 10px 14px 10px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; width: 280px; transition: 0.2s; }
  .search-box input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
  
  .table-card { background: #fff; border-radius: 12px; border: 1px solid #f3f4f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 30px;}
  .table-header { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 18px; margin-bottom: 25px; color: #111827; }
  .table-header i { color: #3b82f6; font-size: 20px; }
  
  .table-responsive { overflow-x: auto; min-height: 300px; }
  .styled-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
  .styled-table th { padding: 12px 15px; color: #6b7280; font-weight: 700; font-size: 12px; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; letter-spacing: 0.5px;}
  .styled-table td { padding: 16px 15px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
  
  /* Efek Hover Background pada Baris Tabel */
  .styled-table tbody tr { transition: background-color 0.2s ease; }
  .styled-table tbody tr:hover { background-color: #f1f5f9; }
  
  .item-id { font-weight: 600; color: #6b7280; }
  .item-title { font-weight: 700; color: #111827; max-width: 250px; line-height: 1.4; }
  .badge-kategori { background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
  
  .doc-thumbnails { display: flex; flex-wrap: wrap; gap: 4px; max-width: 90px; }
  
  /* Efek popup zoom dihilangkan, diganti opacity saat dihover agar simpel */
  .doc-thumb { width: 36px; height: 36px; border-radius: 6px; object-fit: cover; background: #e5e7eb; border: 1px solid #f3f4f6; cursor: pointer; transition: 0.2s; }
  .doc-thumb:hover { opacity: 0.7; }
  
  .doc-more { width: 36px; height: 36px; border-radius: 6px; background: #f9fafb; border: 1px solid #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.2s; }
  .doc-more:hover { background: #e5e7eb; color: #111827; }
  
  .action-btns { display: flex; gap: 8px; }
  .btn-icon { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; color: #9ca3af; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: 0.2s; }
  
  /* Pewarnaan Icon Action */
  .btn-icon:hover { background: #f9fafb; color: #6b7280; }
  .btn-icon.edit { color: #2563eb; } /* Biru */
  .btn-icon.edit:hover { background: #eff6ff; border-color: #bfdbfe; }
  .btn-icon.delete { color: #dc2626; } /* Merah */
  .btn-icon.delete:hover { background: #fef2f2; border-color: #fecaca; }
  
  .pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; font-size: 14px; color: #6b7280; padding-top: 20px; border-top: 1px solid #f3f4f6; }
  .pagination { display: flex; gap: 6px; }
  .page-item { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 6px; background: #fff; color: #374151; cursor: pointer; text-decoration: none; font-weight: 500; font-family: inherit; transition: 0.2s;}
  .page-item.active { background: #2563eb; color: #fff; border-color: #2563eb; }
  .page-item:hover:not(.active):not(:disabled) { background: #f9fafb; }
  .page-item:disabled { opacity: 0.5; cursor: not-allowed; }

  /* Styling Lightbox Modal untuk Gambar (Tanpa Swipe) */
  .image-lightbox { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; background: rgba(0,0,0,0.85); flex-direction: column; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
  .lightbox-content { max-width: 90%; max-height: 80vh; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); object-fit: contain; }
  .lightbox-close { position: absolute; top: 20px; right: 40px; font-size: 40px; color: white; cursor: pointer; text-shadow: 0 2px 4px rgba(0,0,0,0.5); transition: 0.2s; z-index: 100000;}
  .lightbox-close:hover { color: #ef4444; }
  .lightbox-actions { margin-top: 20px; display: flex; gap: 10px; z-index: 100000;}
</style>

<div class="page-head">
  <div>
    <h2>Logbook Jurnal Kegiatan</h2>
    <p style="color: #6b7280; margin-top: 5px;">Dokumentasi kegiatan operasional tim TMB.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;"><i class="ri-add-line"></i> Tulis Jurnal</button>
</div>

<div class="table-controls">
    <div class="filter-tabs">
        <button class="btn-filter active" onclick="setFilter('semua', this)">Semua</button>
        <button class="btn-filter" onclick="setFilter('belum selesai', this)">Belum Selesai</button>
        <button class="btn-filter" onclick="setFilter('proses', this)">Proses</button>
        <button class="btn-filter" onclick="setFilter('selesai', this)">Selesai</button>
    </div>
    <div class="search-box">
        <i class="ri-search-line"></i>
        <input type="text" id="searchInput" placeholder="Cari kegiatan, ID, atau petugas..." onkeyup="handleSearch(this.value)">
    </div>
</div>

<div class="table-card">
  <div class="table-header">
    <i class="ri-list-check"></i> Daftar Kegiatan
  </div>
  
  <?php if($api_error): ?>
      <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          Gagal menghubungi API Notion. Pastikan Token dan Database ID valid.
      </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="styled-table">
      <thead>
        <tr><th>ID</th><th>JUDUL KEGIATAN</th><th>KATEGORI</th><th>PIC</th><th>DOKUMENTASI</th><th>STATUS</th><th>TANGGAL LAPOR</th><th>AKSI</th></tr>
      </thead>
      <tbody id="tableBody">
          <!-- Akan diisi otomatis oleh JS -->
      </tbody>
    </table>
  </div>
  <div class="pagination-container">
    <div id="pageInfo">Menampilkan 0-0 dari 0 Kegiatan</div>
    <div class="pagination" id="paginationNav"></div>
  </div>
</div>

<!-- ========================================== -->
<!-- LIGHTBOX MODAL UNTUK GAMBAR (SINGLE) -->
<!-- ========================================== -->
<div id="imageLightbox" class="image-lightbox">
    <span class="lightbox-close" onclick="closeImageLightbox()">&times;</span>
    <img id="lightboxImg" class="lightbox-content" src="" alt="Dokumentasi">
    <div class="lightbox-actions">
        <a id="lightboxDownloadBtn" href="" target="_blank" class="btn btn-primary" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="ri-download-2-line"></i> Buka & Download
        </a>
    </div>
</div>

<!-- Form Tersembunyi untuk API Delete -->
<form id="deleteForm" action="../api/delete-jurnal.php" method="POST" style="display:none;">
    <input type="hidden" name="page_id" id="deletePageId">
</form>

<!-- ========================================== -->
<!-- MODAL FORM (Create & Edit) -->
<!-- ========================================== -->
<style>
  .notion-modal { max-width: 650px !important; text-align: left; padding: 40px !important; border-radius: 12px; }
  .notion-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #111827; }
  .notion-subtitle { font-size: 24px; font-weight: 700; margin-bottom: 30px; color: #111827; }
  .notion-label { font-size: 16px; font-weight: 700; margin-bottom: 5px; display: block; color: #374151; }
  .notion-hint { font-size: 13px; color: #6b7280; margin-bottom: 12px; display: block; }
  .notion-input { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #f9fafb; margin-bottom: 30px; }
  .notion-input:focus { border-color: #3b82f6; outline: none; background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
  .notion-checkbox-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px; }
  .notion-checkbox-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 30px; }
  .notion-checkbox-item { display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; color: #374151;}
  .notion-checkbox-item input { width: 18px; height: 18px; cursor: pointer; }
  .notion-upload-box { border: 1px dashed #d1d5db; border-radius: 8px; padding: 15px; background: #f9fafb; margin-bottom: 30px; }
  .notion-submit { background: #111827; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
</style>

<div class="modal-overlay" id="jurnalModal">
  <div class="modal-content notion-modal" style="max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <h2 class="notion-title" id="modalTitle">Formulir Jurnal Kegiatan</h2>
        <button class="modal-close" onclick="closeModal()" style="font-size: 28px; color: #6b7280; background:none; border:none; cursor:pointer;">&times;</button>
    </div>
    
    <form id="jurnalForm" action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="page_id" id="formPageId">
        
        <h3 class="notion-subtitle" id="modalSubtitle">Catat Kegiatan disini</h3>
        
        <label class="notion-label">Nama Kegiatan</label>
        <input type="text" class="notion-input" name="nama_kegiatan" id="inputNama" placeholder="Your answer" required>

        <label class="notion-label">Jenis Kegiatan</label>
        <span class="notion-hint">(Select as many as you like)</span>
        <div class="notion-checkbox-list">
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Acara Live / Dialog Luar"> Acara Live / Dialog Luar</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Podcast / Tapping"> Podcast / Tapping</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Troubleshooting / Maintenance"> Troubleshooting / Maintenance</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Supporting Event Kantor"> Supporting Event Kantor</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Keroncong / Senandung Rindu"> Keroncong / Senandung Rindu</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Zoom Meeting"> Zoom Meeting</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Kunjungan"> Kunjungan</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Programa"> Programa</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Editing Audio / Image / Video"> Editing Audio / Image / Video</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="jenis_kegiatan[]" value="Lain-Lain"> Lain-Lain</label>
        </div>

        <label class="notion-label">Date</label>
        <input type="datetime-local" class="notion-input" name="tanggal" id="inputTanggal" required>

        <label class="notion-label">Petugas</label>
        <span class="notion-hint">(Select as many as you like)</span>
        <div class="notion-checkbox-grid">
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Dicky"> Dicky</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Priyo"> Priyo</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Ludfi"> Ludfi</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Anggi"> Anggi</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Deny"> Deny</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Yhanuar"> Yhanuar</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Shofirin"> Shofirin</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Yudha"> Yudha</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Dayat"> Dayat</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Dedy"> Dedy</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Winsa"> Winsa</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Yayang"> Yayang</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Syukur"> Syukur</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Saiful"> Saiful</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Alif"> Alif</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Selvy"> Selvy</label>
            <label class="notion-checkbox-item"><input type="checkbox" name="petugas[]" value="Alfian"> Alfian</label>
        </div>

        <label class="notion-label">Eviden</label>
        <div class="notion-upload-box"><input type="file" name="eviden[]" multiple accept="image/*" style="width: 100%;"></div>

        <label class="notion-label">Eviden 3 (Link)</label>
        <input type="url" class="notion-input" name="eviden_3_link" id="inputEviden3" placeholder="Your answer">

        <div style="margin-top: 10px;">
            <button type="submit" class="notion-submit" id="btnSubmit">Submit</button>
        </div>
    </form>
  </div>
</div>

<!-- SCRIPT LOGIKA JS UNTUK RENDER & FILTER -->
<script>
// Terima data langsung dari eksekusi PHP (getJurnal())
const allEntries = <?= json_encode($jurnalData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

let filteredEntries = [...allEntries];
let currentPage = 1;
const itemsPerPage = 5; 
let currentFilter = 'semua';
let searchQuery = '';

function renderTable() {
    let temp = allEntries;
    
    // Filter by Tabs
    if (currentFilter !== 'semua') {
        temp = temp.filter(item => item.status.toLowerCase() === currentFilter.toLowerCase());
    }
    
    // Filter by Search
    if (searchQuery.trim() !== '') {
        let q = searchQuery.toLowerCase();
        temp = temp.filter(item => item.id.toLowerCase().includes(q) || item.title.toLowerCase().includes(q) || item.op.toLowerCase().includes(q) || item.tag.toLowerCase().includes(q));
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
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 40px; color: #6b7280; font-weight:500;">Tidak ada data ditemukan.</td></tr>`;
    } else {
        pageData.forEach(e => {
            let docsHtml = '';
            let limit = 2; // Hanya tampilkan max 2 foto highlight
            
            for(let i=0; i < Math.min(e.docs.length, limit); i++) {
                // onClick panggil Lightbox simpel dengan URL gambar
                docsHtml += `<img src="${e.docs[i]}" class="doc-thumb" onclick="openImageLightbox('${e.docs[i]}')" onerror="this.src='https://via.placeholder.com/36x36?text=IMG'">`;
            }
            if(e.docs.length > limit) {
                // Tombol +x langsung diarahkan membuka halaman Notion di tab baru
                docsHtml += `<div class="doc-more" onclick="window.open('${e.url}', '_blank')" title="Buka Notion untuk melihat semua foto">+${e.docs.length - limit}</div>`;
            }
            if(e.docs.length === 0) docsHtml = `<span style="color:#9ca3af;font-size:12px;">-</span>`;

            tbody.innerHTML += `
            <tr>
                <td class="item-id">${e.id}</td>
                <td class="item-title">${e.title}</td>
                <td><span class="badge-kategori">${e.tag}</span></td>
                <td style="max-width: 150px; line-height: 1.5;">${e.op}</td>
                <td><div class="doc-thumbnails">${docsHtml}</div></td>
                <td style="font-weight: 700; color: #111827;">${e.status}</td>
                <td>${e.date}</td>
                <td>
                    <div class="action-btns">
                      <a href="${e.url}" target="_blank" class="btn-icon" title="Buka di Notion"><i class="ri-external-link-line"></i></a>
                      <button class="btn-icon edit" title="Edit" onclick="openEditModal('${e.full_id}')"><i class="ri-pencil-line"></i></button>
                      <button class="btn-icon delete" title="Hapus" onclick="deleteJurnal('${e.full_id}')"><i class="ri-delete-bin-line"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    }

    let startInfo = totalItems === 0 ? 0 : startIndex + 1;
    let endInfo = Math.min(endIndex, totalItems);
    document.getElementById('pageInfo').innerText = `Menampilkan ${startInfo}-${endInfo} dari ${totalItems} Kegiatan`;
    renderPaginationButtons(totalPages);
}

function renderPaginationButtons(totalPages) {
    let pg = document.getElementById('paginationNav');
    pg.innerHTML = `<button class="page-item" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Prev</button>`;
    for (let i = 1; i <= totalPages; i++) {
        let activeClass = i === currentPage ? 'active' : '';
        pg.innerHTML += `<button class="page-item ${activeClass}" onclick="changePage(${i})">${i}</button>`;
    }
    pg.innerHTML += `<button class="page-item" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>Next &raquo;</button>`;
}

function changePage(page) {
    let totalPages = Math.ceil(filteredEntries.length / itemsPerPage) || 1;
    if (page >= 1 && page <= totalPages) { currentPage = page; renderTable(); }
}

function setFilter(status, btnElement) {
    currentFilter = status; currentPage = 1;
    document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active'); renderTable();
}

function handleSearch(val) { searchQuery = val; currentPage = 1; renderTable(); }

// ==========================================
// FUNGSI UNTUK LIGHTBOX GAMBAR (SINGLE)
// ==========================================
function openImageLightbox(imgUrl) {
    document.getElementById('lightboxImg').src = imgUrl;
    document.getElementById('lightboxDownloadBtn').href = imgUrl;
    document.getElementById('imageLightbox').style.display = 'flex';
}

function closeImageLightbox() {
    document.getElementById('imageLightbox').style.display = 'none';
}

// Tutup lightbox kalau klik area kosong di luar gambar
document.getElementById('imageLightbox').addEventListener('click', function(e) {
    if (e.target === this) closeImageLightbox();
});

// ==========================================
// FUNGSI UNTUK CRUD 
// ==========================================
function deleteJurnal(full_id) {
    if(confirm("Yakin ingin menghapus data ini? (Akan diarsipkan di Notion)")) {
        document.getElementById('deletePageId').value = full_id;
        document.getElementById('deleteForm').submit();
    }
}

function resetModal() {
    document.getElementById('formPageId').value = "";
    document.getElementById('inputNama').value = "";
    document.getElementById('inputTanggal').value = "";
    document.getElementById('inputEviden3').value = "";
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    document.getElementById('modalTitle').innerText = "Formulir Jurnal Kegiatan";
    document.getElementById('modalSubtitle').innerText = "Catat Kegiatan disini";
    document.getElementById('btnSubmit').innerText = "Submit";
}

function openCreateModal() {
    resetModal();
    document.getElementById('jurnalForm').action = "../api/create-jurnal.php";
    document.getElementById('jurnalModal').classList.add('active');
}

function openEditModal(full_id) {
    resetModal();
    let data = allEntries.find(x => x.full_id === full_id);
    if(data) {
        document.getElementById('modalTitle').innerText = "Edit Jurnal Kegiatan";
        document.getElementById('modalSubtitle').innerText = "Ubah data kegiatan";
        document.getElementById('btnSubmit').innerText = "Simpan Perubahan";
        
        document.getElementById('jurnalForm').action = "../api/update-jurnal.php";
        
        document.getElementById('formPageId').value = data.full_id;
        document.getElementById('inputNama').value = data.title;
        document.getElementById('inputTanggal').value = data.date_raw;
        document.getElementById('inputEviden3').value = data.eviden_3;
        
        document.querySelectorAll('input[name="jenis_kegiatan[]"]').forEach(cb => {
            if(data.tags_arr.includes(cb.value)) cb.checked = true;
        });
        document.querySelectorAll('input[name="petugas[]"]').forEach(cb => {
            if(data.ops_arr.includes(cb.value)) cb.checked = true;
        });
        
        document.getElementById('jurnalModal').classList.add('active');
    }
}

function closeModal(){ document.getElementById('jurnalModal').classList.remove('active'); }
document.getElementById('jurnalModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

// Panggil renderTable saat halaman siap
document.addEventListener("DOMContentLoaded", () => {
    if(allEntries.length > 0) renderTable();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>