<?php
require_once __DIR__.'/../api/troubleshoot.php';

// Ambil data dari Notion
$tickets = getTroubleshoot();

// Keamanan: Pastikan $tickets adalah array (jika API error, jadikan array kosong)
if (!is_array($tickets) || isset($tickets['error'])) {
    $tickets = [];
    $errorMessage = $tickets['error'] ?? 'Gagal memuat data';
}

// Hitung statistik secara DINAMIS berdasarkan data dari Notion
$total = count($tickets);
$selesai = count(array_filter($tickets, fn($t) => $t['status'] === 'Selesai'));
$proses = count(array_filter($tickets, fn($t) => $t['status'] === 'Proses'));
$belum = count(array_filter($tickets, fn($t) => $t['status'] === 'Belum Selesai' || $t['status'] === 'Pending'));

$stats = [
    ['label' => 'Total Kasus', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-tools-line', 'tone' => 'blue'],
    ['label' => 'Selesai', 'value' => $selesai, 'sub' => $total > 0 ? round(($selesai / $total) * 100) . '% resolved' : '0%', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
    ['label' => 'Proses', 'value' => $proses, 'sub' => 'Sedang ditangani', 'icon' => 'ri-loader-4-line', 'tone' => 'orange'],
    ['label' => 'Belum Selesai', 'value' => $belum, 'sub' => 'Butuh tindak lanjut', 'icon' => 'ri-error-warning-line', 'tone' => 'red'],
];

$base = '..'; 
$pageTitle = 'Troubleshoot'; 
$activeMenu = 'troubleshoot';

// Daftar nama petugas (dipakai ulang untuk checkbox di modal)
$petugasList = ['Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Yhanuar','Yudha','Dayat','Saiful','Alif','Winsa','Yayang','Selvy','Alfian'];

include __DIR__ . '/../includes/header.php';
?>

<!-- ===== STYLE KHUSUS MODAL MODERN ===== -->
<style>
/* Modal Overlay & Animation */
.modal-overlay {
  position: fixed; 
  inset: 0; 
  background: rgba(15, 23, 42, 0.6); 
  backdrop-filter: blur(4px);
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 9999;
  opacity: 0; 
  visibility: hidden; 
  transition: all 0.3s ease; 
  padding: 1rem;
}
.modal-overlay.active { opacity: 1; visibility: visible; }

/* Modal Container */
.modal-content {
  background: #ffffff; 
  border-radius: 16px; 
  width: 100%; 
  max-width: 680px; 
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden; /* Hindari scroll di level ini */
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  transform: translateY(20px) scale(0.95); 
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-overlay.active .modal-content { transform: translateY(0) scale(1); }

/* Form Wrapper (PENTING agar flexbox turun ke modal-body) */
#newTicketForm {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0; /* Wajib ada agar bisa di-scroll */
  overflow: hidden;
}

/* Modal Header (Tidak melayang) */
.modal-header {
  display: flex; 
  justify-content: space-between; 
  align-items: center;
  padding: 1.25rem 1.5rem; 
  border-bottom: 1px solid #e5e7eb; 
  background: #ffffff; 
  position: relative; 
  flex-shrink: 0;
  border-radius: 16px 16px 0 0;
}
.modal-title { font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0; }
.modal-close {
  background: #f1f5f9; 
  border: none; 
  width: 34px; 
  height: 34px; 
  border-radius: 8px;
  font-size: 1.5rem; 
  line-height: 1; 
  color: #64748b; 
  cursor: pointer; 
  transition: all 0.2s;
  display: flex; 
  align-items: center; 
  justify-content: center;
}
.modal-close:hover { background: #e2e8f0; color: #0f172a; }

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
  #ticketTable {
    min-width: 720px !important;
  }
  #ticketTable th, #ticketTable td {
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
  .form-row {
    flex-direction: column !important;
    gap: 10px !important;
  }
}

/* Modal Body (Hanya bagian ini yang di-scroll) */
.modal-body { 
  padding: 1.5rem; 
  flex: 1 1 auto; 
  min-height: 0; /* Wajib ada agar scrollbar berfungsi */
  overflow-y: auto; 
  background: #ffffff; 
}

/* Scrollbar tipis agar rapi */
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

/* Modal Footer (Tidak melayang) */
.modal-footer {
  display: flex; 
  justify-content: flex-end; 
  gap: 0.75rem; 
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb; 
  background: #ffffff; 
  position: relative; 
  flex-shrink: 0;
  border-radius: 0 0 16px 16px;
}

/* Form Elements */
.form-group { margin-bottom: 1.5rem; }
.form-label { display: block; font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
.required-star { color: #ef4444; }
.form-hint { font-size: 0.8rem; color: #64748b; font-weight: 400; margin-left: 0.25rem; }

.form-input {
  width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.95rem; color: #0f172a; background: #fff; transition: all 0.2s; outline: none; font-family: inherit;
}
.form-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }

/* Selection Cards (Bidang) */
.selection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.75rem; }
.selection-card {
  position: relative; cursor: pointer; border: 2px solid #e2e8f0; border-radius: 12px;
  padding: 1rem; transition: all 0.2s ease; background: #fff;
}
.selection-card:hover { border-color: #94a3b8; background: #f8fafc; }
.selection-card input[type="radio"] { position: absolute; opacity: 0; }
.selection-card input[type="radio"]:checked + .card-content { color: #2563eb; }
.selection-card:has(input[type="radio"]:checked) {
  border-color: #2563eb; background: #eff6ff; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
}
.card-content { display: flex; align-items: center; gap: 0.75rem; font-weight: 600; font-size: 0.9rem; color: #475569; transition: color 0.2s; }
.card-content i { font-size: 1.25rem; }

/* Chip Selector (Petugas) */
.chip-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.chip-option { cursor: pointer; user-select: none; position: relative; }
.chip-option input[type="checkbox"] { position: absolute; opacity: 0; }
.chip-content {
  display: inline-block; padding: 0.5rem 1rem; border-radius: 999px;
  background: #f1f5f9; color: #475569; font-size: 0.85rem; font-weight: 600;
  border: 1.5px solid transparent; transition: all 0.2s ease;
}
.chip-option:hover .chip-content { background: #e2e8f0; }
.chip-option input[type="checkbox"]:checked + .chip-content {
  background: #2563eb; color: #fff; border-color: #1d4ed8; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}

/* Modern File Upload */
.upload-zone {
  border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.5rem; text-align: center;
  background: #f8fafc; transition: all 0.2s; cursor: pointer; position: relative;
}
.upload-zone:hover, .upload-zone.dragover { border-color: #2563eb; background: #eff6ff; }
.upload-zone input[type="file"] {
  position: absolute; inset: 0; opacity: 0; cursor: pointer;
}
.upload-icon { font-size: 2rem; color: #64748b; margin-bottom: 0.5rem; }
.upload-text { font-size: 0.9rem; color: #334155; font-weight: 600; }
.upload-hint { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }

/* File Preview Items */
.preview-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.preview-item {
  position: relative; width: 80px; height: 80px; border-radius: 10px;
  overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc;
}
.preview-item img { width: 100%; height: 100%; object-fit: cover; }
.preview-item .remove-btn {
  position: absolute; top: 2px; right: 2px; width: 20px; height: 20px;
  background: rgba(239,68,68,0.9); color: #fff; border: none; border-radius: 50%;
  font-size: 12px; cursor: pointer; display: flex; align-items: center;
  justify-content: center; line-height: 1; transition: transform 0.2s;
}
.preview-item .remove-btn:hover { transform: scale(1.1); }
.preview-item .existing-badge {
  position: absolute; bottom: 2px; left: 2px; background: rgba(37,99,235,0.85);
  color: #fff; font-size: 8px; padding: 1px 5px; border-radius: 4px; font-weight: 700;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem;
  cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;
}
.btn-secondary { background: #fff; color: #475569; border: 1.5px solid #cbd5e1; }
.btn-secondary:hover { background: #f1f5f9; border-color: #94a3b8; }
.btn-primary { background: #2563eb; color: #fff; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); }
.btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
.btn-primary:disabled { background: #93c5fd; cursor: not-allowed; transform: none; }

@keyframes slideInToast {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

/* Lightbox Styles */
.lightbox-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  user-select: none;
}
.lightbox-overlay.active {
  display: flex !important;
}
.lightbox-container {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: default;
}
.lightbox-img {
  max-width: 90vw;
  max-height: 85vh;
  border-radius: 8px;
  object-fit: contain;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
  transition: transform 0.2s ease;
}
.lightbox-close-btn {
  position: fixed;
  top: 20px;
  right: 24px;
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10001;
  transition: background 0.2s;
}
.lightbox-close-btn:hover {
  background: rgba(255, 255, 255, 0.4);
}
.lightbox-nav-btn {
  position: fixed;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: none;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  font-size: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10001;
  transition: background 0.2s, transform 0.2s;
}
.lightbox-nav-btn:hover {
  background: rgba(255, 255, 255, 0.4);
  transform: translateY(-50%) scale(1.08);
}
.lightbox-nav-btn.prev {
  left: 24px;
}
.lightbox-nav-btn.next {
  right: 24px;
}
.lightbox-counter {
  margin-top: 12px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 13px;
  font-weight: 600;
  background: rgba(0, 0, 0, 0.6);
  padding: 4px 14px;
  border-radius: 99px;
}
</style>

<div class="page-head">
  <div>
    <h2>Troubleshoot</h2>
    <p>Catatan dan status penanganan kendala teknis siaran.</p>
  </div>
  <div class="page-head-actions">
    <div class="search-box page-head-search">
      <i class="ri-search-line"></i>
      <input type="text" id="troubleshootSearchInput" placeholder="Cari kendala/petugas/lokasi..." oninput="filterTroubleshootTable(this.value)" style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
    </div>
    <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Lapor Kendala Baru</button>
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

<div class="filter-tabs">
  <div class="filter-tab active" onclick="filterTicket('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterTicket('Belum Selesai', this)">Belum Selesai</div>
  <div class="filter-tab" onclick="filterTicket('Proses', this)">Proses</div>
  <div class="filter-tab" onclick="filterTicket('Selesai', this)">Selesai</div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-list-check-2"></i> Daftar Kendala</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="ticketTable">
      <thead>
        <tr>
          <th>Judul Kendala</th>
          <th>Kategori</th>
          <th>PIC</th>
          <th>Dokumentasi</th>
          <th>Status</th>
          <th>Tanggal Lapor</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Tidak ada data kendala ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($tickets as $t):
            $editPayload = htmlspecialchars(json_encode([
                'page_id'    => $t['page_id'],
                'kendala'    => $t['title'],
                'bidang'     => $t['bidang_raw'],
                'deskripsi'  => $t['deskripsi'],
                'kesimpulan' => $t['kesimpulan'],
                'petugas'    => $t['petugas_arr'],
                'dokumentasi'=> $t['dokumentasi'],
            ]), ENT_QUOTES, 'UTF-8');
          ?>
          <tr data-status="<?= htmlspecialchars($t['status']) ?>">
            <td class="cell-title"><?= htmlspecialchars($t['title']) ?></td>
            <td><span class="tag gray"><?= htmlspecialchars($t['cat']) ?></span></td>
            <td><?= htmlspecialchars($t['pic']) ?></td>
            <td>
              <?php if (!empty($t['dokumentasi'])): 
                $docUrls = array_map(function($d) { return $d['url']; }, $t['dokumentasi']);
                $jsonUrls = htmlspecialchars(json_encode($docUrls), ENT_QUOTES, 'UTF-8');
              ?>
              <div style="display:flex;gap:4px;flex-wrap:wrap;">
                <?php foreach (array_slice($t['dokumentasi'], 0, 3) as $idx => $dok): ?>
                <img src="<?= htmlspecialchars($dok['url']) ?>" loading="lazy" alt="dok" class="doc-thumb" onclick='openLightbox(<?= $jsonUrls ?>, <?= $idx ?>)' style="width:36px;height:36px;object-fit:cover;border-radius:6px;cursor:pointer;border:1px solid var(--border-color);">
                <?php endforeach; ?>
                <?php if (count($t['dokumentasi']) > 3): ?>
                <span style="font-size:10px;color:var(--text-secondary);align-self:center;cursor:pointer;" onclick='openLightbox(<?= $jsonUrls ?>, 3)'>+<?= count($t['dokumentasi']) - 3 ?></span>
                <?php endif; ?>
              </div>
              <?php else: ?>
              <span style="font-size:11px;color:var(--text-secondary);">—</span>
              <?php endif; ?>
            </td>
            <td><span class="status-tag <?= htmlspecialchars($t['cls']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            <td class="cell-sub"><?= htmlspecialchars($t['date']) ?></td>
            <td class="cell-actions">
              <a href="<?= htmlspecialchars($t['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
              </a>
              <?php if (getRole() === 'admin'): ?>
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deleteTicket('<?= htmlspecialchars($t['page_id']) ?>', '<?= htmlspecialchars(addslashes($t['title'])) ?>')">
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

<!-- ===== MODAL LAPOR KENDALA (MODERN UI/UX) ===== -->
<div class="modal-overlay" id="ticketModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Formulir Pencatatan Troubleshoot</h3>
      <button class="modal-close" onclick="closeModal()" aria-label="Tutup">&times;</button>
    </div>
    
    <form id="newTicketForm" onsubmit="handleNewTicket(event)">
      <div class="modal-body">
        <input type="hidden" id="field_page_id" name="page_id" value="">

        <!-- Judul Kendala -->
        <div class="form-group">
          <label class="form-label">Judul Kendala <span class="required-star">*</span> <span class="form-hint">— Singkat dan jelas</span></label>
          <input type="text" id="field_kendala" name="kendala" class="form-input" placeholder="Contoh: Internet studio terputus" required>
        </div>

        <!-- Bidang (Selection Cards) -->
        <div class="form-group">
          <label class="form-label">Bidang Terkait <span class="required-star">*</span></label>
          <div class="selection-grid" id="field_bidang">
            <label class="selection-card">
              <input type="radio" name="bidang" value="Sarana Prasarana" required>
              <div class="card-content">
                <i class="ri-building-line"></i>
                <span>Sarana Prasarana</span>
              </div>
            </label>
            <label class="selection-card">
              <input type="radio" name="bidang" value="Pemancar">
              <div class="card-content">
                <i class="ri-broadcast-line"></i>
                <span>Pemancar</span>
              </div>
            </label>
            <label class="selection-card">
              <input type="radio" name="bidang" value="Studio">
              <div class="card-content">
                <i class="ri-mic-line"></i>
                <span>Studio</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="form-group">
          <label class="form-label">Deskripsi Aktivitas Identifikasi</label>
          <textarea id="field_deskripsi" name="deskripsi" class="form-input" rows="3" placeholder="Jelaskan kronologi & gejala masalah..." style="resize: vertical;"></textarea>
        </div>

        <!-- Kesimpulan -->
        <div class="form-group">
          <label class="form-label">Kesimpulan Penyelesaian</label>
          <input type="text" id="field_kesimpulan" name="kesimpulan" class="form-input" placeholder="Bila sudah ada penyelesaian...">
        </div>

        <!-- Petugas (Chip Selector) -->
        <div class="form-group">
          <label class="form-label">
            Nama Petugas <span class="required-star">*</span> 
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

        <!-- Dokumentasi -->
        <div class="form-group">
          <label class="form-label">Dokumentasi Pendukung <span class="form-hint">(Opsional)</span></label>
          <div class="upload-zone" id="dropzone">
            <input type="file" id="field_dokumentasi" name="dokumentasi[]" multiple accept="image/*">
            <div class="upload-icon"><i class="ri-image-add-line"></i></div>
            <div class="upload-text">Klik atau seret foto ke sini</div>
            <div class="upload-hint">Maksimal 10 MB per file, bisa beberapa file sekaligus (JPG, PNG, WebP)</div>
          </div>
          <div class="preview-grid" id="filePreview" style="display: none;"></div>
          <div class="preview-grid" id="existingDocs" style="display: none;"></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="ri-send-plane-line"></i> <span id="submitBtnLabel">Kirim Laporan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// ===== State untuk file uploads =====
let selectedFiles = []; // File objects baru (belum diupload)
let existingDocs = [];  // URL dokumen yang sudah ada di Notion

function openModal(){ 
  document.getElementById('ticketModal').classList.add('active'); 
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
}
function closeModal(){ 
  document.getElementById('ticketModal').classList.remove('active'); 
  document.body.style.overflow = '';
}

document.getElementById('ticketModal').addEventListener('click', e => { 
  if (e.target === e.currentTarget) closeModal(); 
});

// ===== Drag & Drop =====
const dropzone = document.getElementById('dropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('dragover');
  handleFileSelect(e.dataTransfer.files);
});

document.getElementById('field_dokumentasi').addEventListener('change', function() {
  handleFileSelect(this.files);
});

// ===== File handling =====
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
  } else {
    grid.style.display = 'none';
  }
}

function renderExistingDocs(docs) {
  const grid = document.getElementById('existingDocs');
  grid.innerHTML = '';
  existingDocs = docs || [];
  if (existingDocs.length > 0) {
    grid.style.display = 'flex';
    existingDocs.forEach((doc, idx) => {
      const div = document.createElement('div');
      div.className = 'preview-item';
      const img = document.createElement('img');
      img.src = doc.url;
      img.onerror = () => { img.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect fill="%23f1f5f9" width="80" height="80"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%2394a3b8" font-size="10">Foto</text></svg>'; };
      const badge = document.createElement('span');
      badge.className = 'existing-badge';
      badge.textContent = 'EXISTING';
      const btn = document.createElement('button');
      btn.className = 'remove-btn';
      btn.innerHTML = '&times;';
      btn.onclick = (e) => { e.stopPropagation(); existingDocs.splice(idx, 1); renderExistingDocs(existingDocs); };
      div.appendChild(img);
      div.appendChild(badge);
      div.appendChild(btn);
      grid.appendChild(div);
    });
  } else {
    grid.style.display = 'none';
  }
}

// ===== Upload files ke server =====
async function uploadFiles(files) {
  if (files.length === 0) return [];
  const formData = new FormData();
  files.forEach(f => formData.append('dokumentasi[]', f));
  const res = await fetch('../api/upload-to-notion.php', { method: 'POST', body: formData });
  const data = await res.json();
  if (!data.success) throw new Error(data.error || 'Upload ke Notion gagal');
  return data.files.map(f => ({ id: f.id, name: f.name }));
}

// ===== Lightbox Navigation & Close =====
// Lightbox Navigation managed globally via includes/footer.php

// ===== Mode CREATE =====
function openCreateModal(){
  const form = document.getElementById('newTicketForm');
  form.reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Formulir Pencatatan Troubleshoot';
  document.getElementById('submitBtnLabel').textContent = 'Kirim Laporan';
  selectedFiles = [];
  existingDocs = [];
  document.getElementById('filePreview').innerHTML = '';
  document.getElementById('filePreview').style.display = 'none';
  document.getElementById('existingDocs').innerHTML = '';
  document.getElementById('existingDocs').style.display = 'none';
  openModal();
}

// ===== Mode EDIT =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('newTicketForm');
  form.reset();

  document.getElementById('field_page_id').value = data.page_id;
  document.getElementById('field_kendala').value = data.kendala || '';
  document.getElementById('field_deskripsi').value = data.deskripsi || '';
  document.getElementById('field_kesimpulan').value = data.kesimpulan || '';

  const radio = form.querySelector(`input[name="bidang"][value="${CSS.escape(data.bidang || '')}"]`);
  if (radio) radio.checked = true;

  (data.petugas || []).forEach(nama => {
    const cb = form.querySelector(`input[name="petugas[]"][value="${CSS.escape(nama)}"]`);
    if (cb) cb.checked = true;
  });

  selectedFiles = [];
  document.getElementById('filePreview').innerHTML = '';
  document.getElementById('filePreview').style.display = 'none';
  renderExistingDocs(data.dokumentasi || []);

  document.getElementById('modalTitle').textContent = 'Edit Laporan Troubleshoot';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

// ===== STATE PAGINATION =====
let currentPage = 1;
const rowsPerPage = 5;
let currentStatusFilter = 'Semua';
let currentSearchQuery = '';

function filterTroubleshootTable(val) {
  currentSearchQuery = (val || '').toLowerCase().trim();
  currentPage = 1;
  updateTablePagination();
}

function filterTicket(status, el){
  if (el) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }
  currentStatusFilter = status;
  currentPage = 1;
  updateTablePagination();
}

// Pagination dipadatkan supaya muat di layar HP
function getPaginationRange(current, total, delta = 1) {
  const result = [];
  let prev = 0;
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      if (prev && i - prev > 1) result.push('...');
      result.push(i);
      prev = i;
    }
  }
  return result;
}

function changePage(page) {
  currentPage = page;
  updateTablePagination();
}

function updateTablePagination() {
  const allRows = Array.from(document.querySelectorAll('#ticketTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');
  
  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  const filteredRows = allRows.filter(row => {
    const matchFilter = currentStatusFilter === 'Semua' || row.dataset.status === currentStatusFilter;
    const text = row.innerText.toLowerCase();
    const matchSearch = !currentSearchQuery || text.includes(currentSearchQuery);
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
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} kendala`;

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

// ===== Submit (Create/Update) =====
async function handleNewTicket(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;

  const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
  if (petugasChecked.length === 0) { showToast('⚠️ Pilih minimal satu Nama Petugas!', 'error'); return; }

  submitBtn.disabled = true;
  submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mengupload...';

  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  try {
    let uploadedFiles = [];
    if (selectedFiles.length > 0) {
      submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mengupload ke Notion...';
      uploadedFiles = await uploadFiles(selectedFiles);
    }

    submitBtnLabel.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyimpan...';

    const payload = {
      kendala: form.kendala.value.trim(),
      bidang: form.bidang.value,
      deskripsi: form.deskripsi.value.trim(),
      kesimpulan: form.kesimpulan.value.trim(),
      petugas: petugasChecked,
      dokumentasi_file_ids: uploadedFiles,
      dokumentasi_keep: existingDocs
    };
    if (isEdit) payload.page_id = pageId;

    const endpoint = isEdit ? '../api/update-troubleshoot.php' : '../api/create-troubleshoot.php';
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
      selectedFiles = [];
      existingDocs = [];
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

async function deleteTicket(pageId, title) {
  customConfirm(
    'Hapus Laporan',
    `Hapus laporan "${title}"? Laporan akan dipindahkan ke Trash di Notion.`,
    async () => {
      try {
        const res = await fetch('../api/delete-troubleshoot.php', {
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

// showToast() is provided globally by /assets/css/js/sonner.js
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>