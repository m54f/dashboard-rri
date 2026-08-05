<?php
 $base = '..'; 
 $pageTitle = 'Peminjaman Alat'; 
 $activeMenu = 'peminjaman';

require_once __DIR__.'/../config/notion.php';
require_once __DIR__.'/../api/peminjaman.php';

// Fungsi cURL (diperlukan untuk ambil stok)
if (!function_exists('notionCurl')) {
    function notionCurl($method, $url, $body = []) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . NOTION_TOKEN,
                'Notion-Version: 2022-06-28',
                'Content-Type: application/json',
            ],
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        } elseif ($method === 'PATCH') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }
}

 $items = getPeminjaman();

if (!is_array($items) || isset($items['error'])) {
    $errorMessage = $items['error'] ?? 'Gagal memuat data';
    $items = [];
}

 $total       = count($items);
 $dipinjam    = count(array_filter($items, fn($i) => $i['status'] === 'Dipinjam'));
 $terlambat   = count(array_filter($items, fn($i) => $i['status'] === 'Terlambat'));
 $dikembalikan = count(array_filter($items, fn($i) => $i['status'] === 'Dikembalikan'));

 $stats = [
    ['label' => 'Sedang Dipinjam', 'value' => $dipinjam, 'sub' => 'Di lapangan', 'icon' => 'ri-hand-heart-line', 'tone' => 'orange'],
    ['label' => 'Dikembalikan', 'value' => $dikembalikan, 'sub' => 'Sudah selesai', 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
    ['label' => 'Terlambat', 'value' => $terlambat, 'sub' => 'Perlu ditindaklanjuti', 'icon' => 'ri-alarm-warning-line', 'tone' => 'red'],
    ['label' => 'Total Transaksi', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-exchange-line', 'tone' => 'blue'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h2>Peminjaman Alat</h2>
    <p>Kelola peminjaman dan pengembalian alat siaran.</p>
  </div>
  <div class="page-head-actions">
    <div class="search-box page-head-search">
      <i class="ri-search-line"></i>
      <input type="text" id="searchInput" placeholder="Cari kegiatan/PIC/alat..." oninput="handleSearch()" style="border:none !important; outline:none !important; box-shadow:none !important; background:transparent !important; width:100%;">
    </div>
    <a href="peminjaman_form.php" class="btn btn-primary"><i class="ri-add-line"></i> Catat Peminjaman</a>
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

<!-- Container Filter -->
<div style="display:flex; justify-content:flex-start; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:1rem;">
    <div class="filter-tabs" style="margin-bottom:0;">
      <div class="filter-tab active" onclick="filterPinjam('Semua', this)">Semua</div>
      <div class="filter-tab" onclick="filterPinjam('Dipinjam', this)">Dipinjam</div>
      <div class="filter-tab" onclick="filterPinjam('Terlambat', this)">Terlambat</div>
      <div class="filter-tab" onclick="filterPinjam('Dikembalikan', this)">Dikembalikan</div>
    </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-service-line"></i> Riwayat Peminjaman</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="pinjamTable">
      <thead>
        <tr>
          <th>Kegiatan</th>
          <th>PIC</th>
          <th>Alat</th>
          <th>Lokasi</th>
          <th>Tgl Pinjam</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-secondary);">
              Tidak ada data peminjaman.
            </td>
          </tr>
        <?php else: foreach ($items as $it): ?>
        <tr data-status="<?= htmlspecialchars($it['status']) ?>"
            data-kegiatan="<?= htmlspecialchars($it['kegiatan']) ?>"
            data-jumlah-alat="<?= htmlspecialchars(substr_count($it['alat_display'], ',') + 1) ?>"
            data-tgl-pinjam="<?= htmlspecialchars($it['tgl_pinjam'] ?? '-') ?>">
          <td class="cell-title"><?= htmlspecialchars($it['kegiatan']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['pic']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['alat_display']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['lokasi']) ?></td>
          <td><?= htmlspecialchars($it['tgl_pinjam'] ?? '-') ?></td>
          <td><span class="status-tag <?= $it['cls'] ?>"><?= htmlspecialchars($it['status']) ?></span></td>
          <td class="cell-actions">
            <a href="<?= htmlspecialchars($it['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
              <i class="ri-external-link-line"></i>
            </a>
            <?php if ($it['status'] === 'Dipinjam' || $it['status'] === 'Terlambat'): ?>
            <button type="button" class="icon-action return" title="Tandai Dikembalikan" 
                    onclick="markReturned('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>', this)">
              <i class="ri-checkbox-circle-line"></i>
            </button>
            <?php endif; ?>
            <?php if (getRole() === 'admin'): ?>
            <a href="peminjaman_form.php?id=<?= htmlspecialchars($it['page_id']) ?>" class="icon-action edit" title="Edit">
              <i class="ri-pencil-line"></i>
            </a>
            <button type="button" class="icon-action delete" title="Hapus" 
                    onclick="deleteItem('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>')">
              <i class="ri-delete-bin-line"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  
  <!-- PAGING CONTAINER -->
  <div class="pagination-wrap" id="paginationWrap" style="display: none; justify-content: space-between; align-items: center; padding: 1rem; border-top: 1px solid #e2e8f0; background: #fff; border-radius: 0 0 8px 8px;">
    <div class="pagination-info" id="paginationInfo" style="font-size: 0.85rem; color: #64748b;">Menampilkan data</div>
    <div class="pagination-buttons" id="paginationButtons" style="display: flex; gap: 8px; align-items: center;"></div>
  </div>
  
</div>

<style>
  .cell-actions { display:flex; gap:0.4rem; align-items:center; }
  .icon-action { 
    background:none; border:none; cursor:pointer; 
    font-size:1rem; padding:0.3rem; border-radius:6px; 
    text-decoration: none; transition: all 0.2s;
  }
  .icon-action.view { color:#6b7280; }
  .icon-action.edit { color:#3b82f6; }
  .icon-action.return { color:#22c55e; }
  .icon-action.delete { color:#ef4444; }
  .icon-action:hover { background:rgba(0,0,0,0.05); transform: scale(1.1); }/* ===== MODAL PENGEMBALIAN — Figma Vibe Design ===== */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999;
  opacity: 0; visibility: hidden;
  transition: opacity 0.25s ease, visibility 0.25s ease;
  padding: 1rem;
}
.modal-overlay.active { opacity: 1; visibility: visible; }

/* Modal card: 480px, border-radius 16px, shadow Large */
.modal-content {
  background: #ffffff;
  border-radius: 16px;
  width: 480px; max-width: calc(100vw - 32px);
  max-height: 90vh;
  display: flex; flex-direction: column;
  /* align-items default = stretch: semua child otomatis penuh lebar */
  overflow: hidden;
  box-shadow: 0px 15px 50px rgba(0, 0, 0, 0.3);
  transform: translateY(24px) scale(0.96);
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-overlay.active .modal-content { transform: translateY(0) scale(1); }

/* Header: flex-column, padding 32px 32px 24px, gap 4px */
.modal-header {
  display: flex; flex-direction: column;
  justify-content: center; align-items: flex-start;
  padding: 32px 32px 24px;
  gap: 4px;
  align-self: stretch;
  position: relative;
  flex-shrink: 0;
}

/* Title: Poppins 24px/600, #323338 */
.modal-title {
  font-family: 'Poppins', 'Figtree', sans-serif;
  font-style: normal; font-weight: 600;
  font-size: 24px; line-height: 30px;
  letter-spacing: -0.1px;
  color: #323338;
  margin: 0; align-self: stretch;
}

/* Subtitle: Figtree 16px/400, #323338 */
.modal-subtitle {
  font-family: 'Figtree', sans-serif;
  font-style: normal; font-weight: 400;
  font-size: 16px; line-height: 22px;
  color: #323338;
  margin: 0; align-self: stretch;
}

/* Close button: absolute 32×32, top 24px right 24px */
.modal-close {
  position: absolute; top: 24px; right: 24px;
  display: flex; align-items: center; justify-content: center;
  padding: 0; gap: 8px;
  width: 32px; height: 32px;
  border-radius: 4px; border: none;
  background: transparent; color: #676879;
  font-size: 1.25rem; cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.modal-close:hover { background: #f0f0f0; color: #323338; }
.modal-close:focus-visible { outline: 2px solid #0073EA; outline-offset: 2px; }

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

/* Body: padding 0 32px 32px */
.modal-body {
  padding: 0 32px 32px;
  flex: 1 1 auto; min-height: 0;
  overflow-y: auto; background: #fff;
  align-self: stretch;
}
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

.return-info-banner {
  background: #f9fafb; border: 1px solid #e5e7eb;
  border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;
}
.info-banner-label {
  display: block; font-size: 0.7rem; text-transform: uppercase;
  letter-spacing: 0.07em; color: #9ca3af; font-weight: 600; margin-bottom: 0.4rem;
}
.info-banner-title {
  display: block; font-size: 1rem; color: #111827;
  font-weight: 700; line-height: 1.4; word-break: break-word; margin-bottom: 0.5rem;
}
.info-banner-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; font-size: 0.8rem; color: #6b7280; }
.info-banner-meta span { display: inline-flex; align-items: center; gap: 0.3rem; }
.info-banner-meta i { font-size: 0.875rem; }

.r-form-group { margin-bottom: 1.25rem; }
.r-form-group:last-child { margin-bottom: 0; }
.r-form-label {
  display: inline-flex; align-items: center; gap: 0.375rem;
  font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 0.45rem;
}
.r-form-label-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.r-form-label-row .r-form-label { margin-bottom: 0; }
.r-required { color: #ef4444; font-weight: 700; }
.char-counter { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
.char-counter.warn { color: #f59e0b; }
.char-counter.danger { color: #ef4444; }
.r-form-input {
  width: 100%; padding: 0.7rem 1rem;
  border: 1.5px solid #e2e8f0; border-radius: 10px;
  font-size: 0.925rem; color: #0f172a; background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none; font-family: inherit; box-sizing: border-box;
}
.r-form-input:focus { border-color: #0073EA; box-shadow: 0 0 0 3px rgba(0,115,234,0.12); }
.r-form-input.input-error { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.select-wrapper { position: relative; }
.r-form-select { appearance: none; -webkit-appearance: none; padding-right: 2.5rem; cursor: pointer; background-color: #fff; }
.select-icon {
  position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%);
  font-size: 1.25rem; color: #64748b; pointer-events: none; transition: color 0.2s;
}
.r-form-select:focus + .select-icon { color: #0073EA; }
.r-form-select option:disabled { color: #94a3b8; font-style: italic; }
.r-form-textarea { resize: vertical; min-height: 84px; line-height: 1.5; }
.r-form-help { display: block; font-size: 0.75rem; color: #94a3b8; margin-top: 0.375rem; }
.r-form-error { display: none; align-items: center; gap: 0.3rem; font-size: 0.8rem; color: #dc2626; margin-top: 0.375rem; font-weight: 500; }
.r-form-error.visible { display: flex; }
.r-form-error i { font-size: 1rem; }

/* Form: flex-column, isi sisa ruang modal, body bisa scroll */
#returnForm {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  width: 100%;
}

/* Footer: flex-row, padding 20px 24px, bg #fff */
.modal-footer {
  display: flex; flex-direction: row;
  justify-content: space-between; align-items: center;
  padding: 20px 24px;
  min-height: 80px; background: #ffffff;
  align-self: stretch; flex-shrink: 0;
}

/* Footer right buttons container: gap 8px */
.modal-footer-right {
  display: flex; flex-direction: row;
  justify-content: flex-end; align-items: center;
  gap: 8px; margin-left: auto;
}

/* Secondary button: Figtree 16px/400, #323338, no border, 40px */
.btn-modal-cancel {
  box-sizing: border-box;
  display: flex; flex-direction: row;
  justify-content: center; align-items: center;
  padding: 0 16px; gap: 8px;
  height: 40px; min-height: 40px; max-height: 40px;
  border-radius: 4px; border: none;
  background: transparent; color: #323338;
  font-family: 'Figtree', inherit;
  font-weight: 400; font-size: 16px; line-height: 22px;
  cursor: pointer; white-space: nowrap;
  transition: background 0.15s;
}
.btn-modal-cancel:hover { background: #f0f0f0; }
.btn-modal-cancel:focus-visible { outline: 2px solid #0073EA; outline-offset: 2px; }

/* Primary button: bg #0073EA, white text, Figtree 16px/400, 40px */
.btn-modal-submit {
  box-sizing: border-box;
  display: flex; flex-direction: row;
  justify-content: center; align-items: center;
  padding: 0 16px; gap: 8px;
  height: 40px; min-height: 40px; max-height: 40px;
  background: #0073EA; border-radius: 4px; border: none;
  font-family: 'Figtree', inherit;
  font-weight: 400; font-size: 16px; line-height: 22px;
  color: #ffffff; cursor: pointer; white-space: nowrap;
  transition: background 0.15s, opacity 0.15s;
}
.btn-modal-submit:hover:not(:disabled) { background: #0060c0; }
.btn-modal-submit:active:not(:disabled) { background: #0052a3; }
.btn-modal-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-modal-submit:focus-visible { outline: 2px solid #0073EA; outline-offset: 2px; }

/* hidden attribute harus override CSS display — prinsip HTML spec */
[hidden] { display: none !important; }
.btn-loading { align-items: center; gap: 0.4rem; }
@keyframes ri-spin { to { transform: rotate(360deg); } }
.ri-spin { animation: ri-spin 0.8s linear infinite; }

@media (max-width: 500px) {
  .modal-content { min-width: unset; border-radius: 12px; }
  .modal-header { padding: 24px 20px 16px; }
  .modal-body { padding: 0 20px 24px; }
  .modal-footer { padding: 0 20px; height: 72px; }
  .modal-title { font-size: 20px; }
}

/* ===== TIPSEEN — Custom Confirm Dialog (Figma Vibe) =====
   288px, dark #323338, border-radius 8px, shadow medium
*/
.tipseen-overlay {
  position: fixed; inset: 0;
  z-index: 99998;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0,0,0,0.35);
  opacity: 0; visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s ease;
}
.tipseen-overlay.active { opacity: 1; visibility: visible; }

/* Tipseen card */
.tipseen {
  position: relative;
  display: flex; flex-direction: column; align-items: center;
  padding: 0;
  width: 288px;
  background: #323338;
  box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
  border-radius: 8px;
  isolation: isolate;
  transform: scale(0.88);
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.tipseen-overlay.active .tipseen { transform: scale(1); }

/* Content wrapper: padding 0 20px 24px */
.tipseen-content {
  display: flex; flex-direction: column; align-items: flex-start;
  padding: 0 20px 24px;
  width: 100%; box-sizing: border-box;
  border-radius: 8px;
}

/* Text block: padding 16px 0 20px, gap 4px */
.tipseen-text {
  display: flex; flex-direction: column;
  justify-content: center; align-items: flex-start;
  padding: 16px 0 20px; gap: 4px;
  align-self: stretch;
}

/* Title: Figtree 16px/700, white */
.tipseen-title {
  font-family: 'Figtree', sans-serif;
  font-style: normal; font-weight: 700;
  font-size: 16px; line-height: 22px;
  color: #ffffff;
  align-self: stretch;
}

/* Message: Figtree 14px/400, white */
.tipseen-message {
  font-family: 'Figtree', sans-serif;
  font-style: normal; font-weight: 400;
  font-size: 14px; line-height: 20px;
  color: #ffffff;
  align-self: stretch;
}

/* Footer: flex-row, justify-end, gap 8px */
.tipseen-footer {
  display: flex; flex-direction: row;
  justify-content: flex-end; align-items: center;
  gap: 8px;
  align-self: stretch;
}

/* Back/Batal — low emphasis on dark bg */
.tipseen-btn-back {
  display: flex; align-items: center; justify-content: center;
  padding: 0 8px;
  height: 32px; min-height: 32px; max-height: 32px;
  border-radius: 4px; border: none;
  background: transparent; color: #ffffff;
  font-family: 'Figtree', inherit;
  font-weight: 400; font-size: 14px; line-height: 20px;
  cursor: pointer; white-space: nowrap;
  transition: background 0.15s;
}
.tipseen-btn-back:hover { background: rgba(255,255,255,0.12); }

/* Confirm — high emphasis on dark bg: white bg, dark text */
.tipseen-btn-confirm {
  display: flex; align-items: center; justify-content: center;
  padding: 0 8px;
  height: 32px; min-height: 32px; max-height: 32px;
  background: #ffffff; border-radius: 4px; border: none;
  font-family: 'Figtree', inherit;
  font-weight: 400; font-size: 14px; line-height: 20px;
  color: #323338;
  cursor: pointer; white-space: nowrap;
  transition: opacity 0.15s;
}
.tipseen-btn-confirm:hover { opacity: 0.88; }

/* Close: absolute, top 8px right 8px, 20×20 */
.tipseen-close {
  position: absolute; top: 8px; right: 8px;
  display: flex; align-items: center; justify-content: center;
  width: 20px; height: 20px;
  border-radius: 4px; border: none;
  background: transparent; color: #676879;
  cursor: pointer; padding: 0;
  transition: color 0.15s;
  z-index: 1;
}
.tipseen-close:hover { color: #ffffff; }
</style>

<script>
// Variabel State Pagination & Filter
let currentPage = 1;
const rowsPerPage = 10;
let currentFilter = 'Semua';
let searchQuery = '';

function handleSearch() {
  searchQuery = document.getElementById('searchInput').value.toLowerCase();
  currentPage = 1;
  updateTablePagination();
}

function filterPinjam(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  currentFilter = status;
  currentPage = 1;
  updateTablePagination();
}

function changePage(page) {
  currentPage = page;
  updateTablePagination();
}

function jumpToPage(val, maxPage) {
  let p = parseInt(val, 10);
  if (!isNaN(p) && p >= 1 && p <= maxPage) {
    changePage(p);
  } else if (p > maxPage) {
    changePage(maxPage);
  } else {
    changePage(1); 
  }
}

function updateTablePagination() {
  const allRows = Array.from(document.querySelectorAll('#pinjamTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');

  // Abaikan baris pesan kosong
  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  // Filter gabungan: Tab Filter & Teks Pencarian
  const filteredRows = allRows.filter(row => {
    const statusMatch = currentFilter === 'Semua' || row.dataset.status === currentFilter;
    const textContent = row.textContent.toLowerCase();
    const searchMatch = !searchQuery || textContent.includes(searchQuery);
    return statusMatch && searchMatch;
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
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} peminjaman`;

    paginationButtons.style.display = 'flex';
    paginationButtons.style.alignItems = 'center';
    paginationButtons.style.gap = '0.5rem';

    let buttonsHTML = '';

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const prevStyle = currentPage === 1 ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${prevStyle}" onclick="changePage(${currentPage - 1})" ${prevDisabled}>&laquo; Prev</button>`;

    buttonsHTML += `<input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="${currentPage}" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="jumpToPage(this.value, ${totalPages})" onkeydown="if(event.key === 'Enter') jumpToPage(this.value, ${totalPages})">`;

    buttonsHTML += `<span style="font-size:0.9rem; color:#475569; display:inline-flex; align-items:center;">dari ${totalPages}</span>`;

    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    const nextStyle = currentPage === totalPages ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
    buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${nextStyle}" onclick="changePage(${currentPage + 1})" ${nextDisabled}>Next &raquo;</button>`;

    paginationButtons.innerHTML = buttonsHTML;
  }
}

// ===== MODAL PENGEMBALIAN =====
let _returnPageId = '';
let _currentRow = null;
let lastFocusedElement = null;

function markReturned(pageId, kegiatan, btnElement) {
  _returnPageId = pageId;
  _currentRow = btnElement.closest('tr');
  lastFocusedElement = document.activeElement;
  
  // Ambil data dari row
  const jumlahAlat = _currentRow.dataset.jumlahAlat || '1';
  const tglPinjam = _currentRow.dataset.tglPinjam || '-';
  
  // Set nama kegiatan di banner
  document.getElementById('returnKegiatanLabel').textContent = kegiatan;
  
  // Set meta info
  document.getElementById('metaAlat').textContent = jumlahAlat + ' alat';
  document.getElementById('metaTanggal').textContent = tglPinjam;
  
  // Reset form
  const form = document.getElementById('returnForm');
  form.reset();
  document.getElementById('fieldPic').classList.remove('input-error');
  document.getElementById('picError').classList.remove('visible');
  updateCharCounter();
  
  // Reset button
  const submitBtn = document.getElementById('returnSubmitBtn');
  const submitLabel = document.getElementById('returnSubmitLabel');
  const submitLoading = document.getElementById('returnSubmitLoading');
  submitBtn.disabled = false;
  submitLabel.hidden = false;
  submitLoading.hidden = true;
  submitLabel.innerHTML = '<i class="ri-checkbox-circle-line"></i> Konfirmasi Pengembalian';
  
  // Kosongkan & tampilkan loading di dropdown
  const sel = document.getElementById('fieldPic');
  sel.innerHTML = '<option value="" disabled selected>Memuat daftar petugas...</option>';
  
  // Buka modal
  document.getElementById('returnModal').classList.add('active');
  document.body.style.overflow = 'hidden';
  
  // Focus ke select pertama setelah animasi
  setTimeout(() => document.getElementById('fieldPic').focus(), 100);
  
  // Tambah ESC listener
  document.addEventListener('keydown', handleEscKey);
  
  // Fetch daftar petugas
  fetchPetugasList();
}

async function fetchPetugasList() {
  const sel = document.getElementById('fieldPic');
  try {
    const res = await fetch('../api/get-petugas-list.php');
    const data = await res.json();
    if (data.success && data.data.length > 0) {
      sel.innerHTML = '<option value="" disabled selected>— Pilih PIC Pengembali —</option>';
      data.data.forEach(name => {
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        sel.appendChild(opt);
      });
    } else {
      sel.innerHTML = '<option value="" disabled selected>Gagal memuat petugas</option>';
    }
  } catch(e) {
    sel.innerHTML = '<option value="" disabled selected>Gagal memuat petugas</option>';
  }
}

function closeReturnModal() {
  document.getElementById('returnModal').classList.remove('active');
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleEscKey);
  
  // Restore focus
  if (lastFocusedElement) {
    setTimeout(() => lastFocusedElement.focus(), 100);
  }
}

function handleEscKey(e) {
  if (e.key === 'Escape') closeReturnModal();
}

document.addEventListener('DOMContentLoaded', () => {
  // Init pagination
  updateTablePagination();
  
  // Close saat klik overlay
  document.getElementById('returnModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeReturnModal();
  });
  
  // Character counter untuk textarea
  const fieldCatatan = document.getElementById('fieldCatatan');
  fieldCatatan.addEventListener('input', updateCharCounter);
});

function updateCharCounter() {
  const fieldCatatan = document.getElementById('fieldCatatan');
  const counter = document.getElementById('catatanCounter');
  const len = fieldCatatan.value.length;
  const max = 300;
  counter.textContent = `${len} / ${max}`;
  counter.classList.remove('warn', 'danger');
  if (len > max * 0.9) counter.classList.add('danger');
  else if (len > max * 0.75) counter.classList.add('warn');
}

async function submitReturn(event) {
  event.preventDefault();
  
  const pic = document.getElementById('fieldPic').value.trim();
  const picError = document.getElementById('picError');
  
  // Validasi
  if (!pic) {
    document.getElementById('fieldPic').classList.add('input-error');
    picError.classList.add('visible');
    document.getElementById('fieldPic').focus();
    return;
  }
  
  document.getElementById('fieldPic').classList.remove('input-error');
  picError.classList.remove('visible');

  const kondisi = document.getElementById('fieldKondisi').value;
  const catatan = document.getElementById('fieldCatatan').value.trim();

  const submitBtn = document.getElementById('returnSubmitBtn');
  const submitLabel = document.getElementById('returnSubmitLabel');
  const submitLoading = document.getElementById('returnSubmitLoading');
  
  submitBtn.disabled = true;
  submitLabel.hidden = true;
  submitLoading.hidden = false;

  try {
    const res = await fetch('../api/mark-returned.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        page_id: _returnPageId,
        nama_pic_pengembali: pic,
        kondisi_alat: kondisi,
        catatan_pengembalian: catatan
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message, 'success');
      closeReturnModal();
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast('❌ ' + (data.error || 'Gagal menyimpan'), 'error');
      submitBtn.disabled = false;
      submitLabel.hidden = false;
      submitLoading.hidden = true;
    }
  } catch(err) {
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
    submitBtn.disabled = false;
    submitLabel.hidden = false;
    submitLoading.hidden = true;
  }
}

async function deleteItem(pageId, kegiatan) {
  customConfirm(
    'Hapus Peminjaman',
    `Hapus data "${kegiatan}"? Data akan dipindahkan ke Trash di Notion.`,
    async () => {
      try {
        const res = await fetch('../api/delete-peminjaman.php', {
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

/**
 * customConfirm — mengganti browser confirm() dengan Tipseen Figma component
 * @param {string} title - Judul popup
 * @param {string} message - Pesan popup
 * @param {Function} onConfirm - Callback jika dikonfirmasi
 * @param {string} confirmLabel - Label tombol konfirmasi
 */
function customConfirm(title, message, onConfirm, confirmLabel = 'OK') {
  const overlay  = document.getElementById('tipseenOverlay');
  const elTitle  = document.getElementById('tipseenTitle');
  const elMsg    = document.getElementById('tipseenMessage');
  const btnOk    = document.getElementById('tipseenBtnConfirm');
  const btnCancel= document.getElementById('tipseenBtnBack');
  const btnClose = document.getElementById('tipseenClose');

  elTitle.textContent  = title;
  elMsg.textContent    = message;
  btnOk.textContent    = confirmLabel;

  overlay.classList.add('active');
  setTimeout(() => btnOk.focus(), 50);

  function close() {
    overlay.classList.remove('active');
    btnOk.onclick     = null;
    btnCancel.onclick = null;
    btnClose.onclick  = null;
    overlay.onclick   = null;
    document.removeEventListener('keydown', onKey);
  }
  function onKey(e) { if (e.key === 'Escape') close(); }

  btnOk.onclick     = () => { close(); onConfirm(); };
  btnCancel.onclick = close;
  btnClose.onclick  = close;
  overlay.onclick   = (e) => { if (e.target === overlay) close(); };
  document.addEventListener('keydown', onKey);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ===== MODAL PENGEMBALIAN ===== -->
<div class="modal-overlay" id="returnModal" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="returnModalTitle">Konfirmasi Pengembalian</h3>
      <p class="modal-subtitle">Tandai alat sebagai sudah kembali</p>
      <button class="modal-close" onclick="closeReturnModal()" aria-label="Tutup modal" title="Tutup (Esc)">
        <i class="ri-close-line"></i>
      </button>
    </div>

    <form id="returnForm" onsubmit="submitReturn(event)" novalidate>
      <div class="modal-body">

        <!-- Info card kegiatan -->
        <div class="return-info-banner" role="status">
          <span class="info-banner-label">KEGIATAN</span>
          <strong id="returnKegiatanLabel" class="info-banner-title">—</strong>
          <div class="info-banner-meta" id="returnMetaInfo">
            <span><i class="ri-tools-line"></i> <span id="metaAlat">—</span></span>
            <span><i class="ri-calendar-line"></i> <span id="metaTanggal">—</span></span>
          </div>
        </div>

        <!-- PIC Pengembali (wajib) -->
        <div class="r-form-group">
          <label class="r-form-label" for="fieldPic">
            Nama PIC Pengembali
            <span class="r-required" aria-hidden="true">*</span>
          </label>
          <div class="select-wrapper">
            <select id="fieldPic" name="nama_pic_pengembali" class="r-form-input r-form-select" required
                    aria-describedby="picHelp">
              <option value="" disabled selected>Pilih PIC Pengembali</option>
            </select>
            <i class="ri-arrow-down-s-line select-icon" aria-hidden="true"></i>
          </div>
          <span class="r-form-help" id="picHelp" style="color:#6b7280;">Petugas yang menerima pengembalian alat</span>
          <span class="r-form-error" id="picError" role="alert">
            <i class="ri-error-warning-line"></i> PIC pengembali wajib dipilih
          </span>
        </div>

        <!-- Kondisi Alat -->
        <div class="r-form-group">
          <label class="r-form-label" for="fieldKondisi">Kondisi Alat</label>
          <div class="select-wrapper">
            <select id="fieldKondisi" name="kondisi_alat" class="r-form-input r-form-select">
              <option value="" selected>Pilih kondisi (opsional)</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
              <option value="Hilang">Hilang</option>
            </select>
            <i class="ri-arrow-down-s-line select-icon" aria-hidden="true"></i>
          </div>
        </div>

        <!-- Catatan -->
        <div class="r-form-group">
          <div class="r-form-label-row">
            <label class="r-form-label" for="fieldCatatan">Catatan</label>
            <span class="char-counter" id="catatanCounter">0/300</span>
          </div>
          <textarea id="fieldCatatan" name="catatan_pengembalian" class="r-form-input r-form-textarea"
            rows="3" maxlength="300"
            placeholder="Kondisi spesifik, nomor seri, atau catatan tambahan..."
            style="resize:vertical; min-height:80px;"></textarea>
        </div>

      </div>

      <div class="modal-footer">
        <div class="modal-footer-right">
          <button type="button" class="btn-modal-cancel" onclick="closeReturnModal()">Batal</button>
          <button type="submit" class="btn-modal-submit" id="returnSubmitBtn">
            <span id="returnSubmitLabel">Konfirmasi</span>
            <span class="btn-loading" id="returnSubmitLoading" hidden>
              <i class="ri-loader-4-line ri-spin"></i> Memproses...
            </span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ===== TIPSEEN CONFIRM DIALOG ===== -->
<div class="tipseen-overlay" id="tipseenOverlay" role="alertdialog" aria-modal="true"
     aria-labelledby="tipseenTitle" aria-describedby="tipseenMessage">
  <div class="tipseen" id="tipseenBox">
    <!-- Close: absolute top-right 8px -->
    <button class="tipseen-close" id="tipseenClose" aria-label="Tutup">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
      </svg>
    </button>
    <!-- Content: padding 0 20px 24px -->
    <div class="tipseen-content">
      <!-- Text: padding 16px 0 20px, gap 4px -->
      <div class="tipseen-text">
        <div class="tipseen-title" id="tipseenTitle">Konfirmasi</div>
        <div class="tipseen-message" id="tipseenMessage">Apakah Anda yakin?</div>
      </div>
      <!-- Footer: justify-end, gap 8px -->
      <div class="tipseen-footer">
        <!-- Batal: low emphasis on dark -->
        <button class="tipseen-btn-back" id="tipseenBtnBack">Batal</button>
        <!-- Confirm: high emphasis on dark (white bg) -->
        <button class="tipseen-btn-confirm" id="tipseenBtnConfirm">OK</button>
      </div>
    </div>
  </div>
</div>