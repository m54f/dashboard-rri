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
  <a href="peminjaman_form.php" class="btn btn-primary"><i class="ri-add-line"></i> Catat Peminjaman</a>
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
  <div class="filter-tab active" onclick="filterPinjam('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterPinjam('Dipinjam', this)">Dipinjam</div>
  <div class="filter-tab" onclick="filterPinjam('Terlambat', this)">Terlambat</div>
  <div class="filter-tab" onclick="filterPinjam('Dikembalikan', this)">Dikembalikan</div>
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
  .icon-action:hover { background:rgba(0,0,0,0.05); transform: scale(1.1); }

/* ===== MODAL PENGEMBALIAN (UX-Enhanced) ===== */
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

.modal-content {
  background: #ffffff;
  border-radius: 18px;
  width: 100%; max-width: 520px; max-height: 90vh;
  display: flex; flex-direction: column; overflow: hidden;
  box-shadow:
    0 25px 50px -12px rgba(0,0,0,0.25),
    0 0 0 1px rgba(15, 23, 42, 0.04);
  transform: translateY(24px) scale(0.96);
  transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-overlay.active .modal-content { transform: translateY(0) scale(1); }

.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(to bottom, #ffffff, #fafafa);
  flex-shrink: 0;
}
.modal-header-info { display: flex; align-items: center; gap: 0.875rem; }
.modal-icon-wrapper {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  color: #15803d;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(22, 163, 74, 0.1);
}
.modal-title {
  font-size: 1.05rem; font-weight: 700; color: #0f172a;
  margin: 0; line-height: 1.3;
}
.modal-subtitle {
  font-size: 0.8rem; color: #64748b;
  margin: 0.125rem 0 0; font-weight: 500;
}
.modal-close {
  background: transparent; border: none;
  width: 36px; height: 36px;
  border-radius: 8px;
  color: #64748b;
  font-size: 1.25rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.modal-close:hover { background: #f1f5f9; color: #0f172a; }
.modal-close:focus-visible {
  outline: 2px solid #2563eb;
  outline-offset: 2px;
}

.modal-body {
  padding: 1.5rem;
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  background: #fff;
}
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.return-info-banner {
  background: linear-gradient(to right, #f0fdf4, #f0fdfa);
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  padding: 1rem 1.125rem;
  margin-bottom: 1.5rem;
  display: flex; align-items: flex-start; gap: 0.875rem;
  box-shadow: 0 1px 2px rgba(22, 163, 74, 0.05);
}
.info-banner-icon {
  width: 36px; height: 36px;
  border-radius: 8px;
  background: #dcfce7;
  color: #16a34a;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.info-banner-content { flex: 1; min-width: 0; }
.info-banner-label {
  display: block;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #15803d;
  font-weight: 600;
  margin-bottom: 0.25rem;
}
.info-banner-title {
  display: block;
  font-size: 0.95rem;
  color: #064e3b;
  font-weight: 700;
  line-height: 1.4;
  word-break: break-word;
}
.info-banner-meta {
  display: flex; flex-wrap: wrap; gap: 0.5rem;
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #475569;
}
.info-banner-meta span {
  display: inline-flex; align-items: center; gap: 0.25rem;
  padding: 0.2rem 0.5rem;
  background: rgba(255,255,255,0.7);
  border-radius: 6px;
}
.info-banner-meta i { font-size: 0.85rem; color: #64748b; }

.r-form-group { margin-bottom: 1.375rem; }
.r-form-group:last-child { margin-bottom: 0; }

.r-form-label {
  display: inline-flex; align-items: center; gap: 0.375rem;
  font-size: 0.875rem; font-weight: 600; color: #334155;
  margin-bottom: 0.5rem;
}
.r-form-label i { font-size: 1rem; color: #64748b; }
.r-form-label-row {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 0.5rem;
}
.r-form-label-row .r-form-label { margin-bottom: 0; }

.r-required { color: #ef4444; font-weight: 700; }

.char-counter {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
}
.char-counter.warn { color: #f59e0b; }
.char-counter.danger { color: #ef4444; }

.r-form-input {
  width: 100%;
  padding: 0.7rem 1rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.925rem;
  color: #0f172a;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
  font-family: inherit;
  box-sizing: border-box;
}
.r-form-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.r-form-input.input-error {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.select-wrapper { position: relative; }
.r-form-select {
  appearance: none;
  -webkit-appearance: none;
  padding-right: 2.5rem;
  cursor: pointer;
  background-color: #fff;
}
.select-icon {
  position: absolute;
  right: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.25rem;
  color: #64748b;
  pointer-events: none;
  transition: transform 0.2s;
}
.r-form-select:focus + .select-icon { color: #2563eb; }
.r-form-select option:disabled { color: #94a3b8; font-style: italic; }

.r-form-textarea {
  resize: vertical;
  min-height: 84px;
  line-height: 1.5;
}

.r-form-help {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.375rem;
}
.r-form-error {
  display: none;
  align-items: center; gap: 0.3rem;
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 0.375rem;
  font-weight: 500;
}
.r-form-error.visible { display: flex; }
.r-form-error i { font-size: 1rem; }

.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.625rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #fafafa;
  flex-shrink: 0;
}
.btn-modal-cancel,
.btn-modal-submit {
  padding: 0.7rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  font-family: inherit;
  min-width: 110px;
}
.btn-modal-cancel {
  background: #fff; color: #475569;
  border: 1.5px solid #e2e8f0;
}
.btn-modal-cancel:hover {
  background: #f8fafc; border-color: #cbd5e1; color: #0f172a;
}
.btn-modal-cancel:focus-visible { outline: 2px solid #64748b; outline-offset: 2px; }

.btn-modal-submit {
  background: linear-gradient(to bottom, #16a34a, #15803d);
  color: #fff;
  border: none;
  box-shadow: 0 2px 4px rgba(22, 163, 74, 0.25), 0 1px 2px rgba(22, 163, 74, 0.15);
}
.btn-modal-submit:hover:not(:disabled) {
  background: linear-gradient(to bottom, #15803d, #166534);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(22, 163, 74, 0.3);
}
.btn-modal-submit:active:not(:disabled) { transform: translateY(0); }
.btn-modal-submit:disabled {
  background: #bbf7d0; cursor: not-allowed;
  box-shadow: none; transform: none; color: rgba(255,255,255,0.8);
}
.btn-modal-submit:focus-visible { outline: 2px solid #16a34a; outline-offset: 2px; }

.btn-loading { display: inline-flex; align-items: center; gap: 0.4rem; }

@keyframes ri-spin { to { transform: rotate(360deg); } }
.ri-spin { animation: ri-spin 0.8s linear infinite; }

@media (max-width: 480px) {
  .modal-content { border-radius: 16px; max-height: 95vh; }
  .modal-header, .modal-body, .modal-footer { padding-left: 1.125rem; padding-right: 1.125rem; }
  .modal-footer { flex-direction: column-reverse; }
  .btn-modal-cancel, .btn-modal-submit { width: 100%; }
  .modal-subtitle { display: none; }
}
</style>

<script>
function filterPinjam(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#pinjamTable tbody tr').forEach(row => {
    row.style.display = (status === 'Semua' || row.dataset.status === status) ? '' : 'none';
  });
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
  document.getElementById('returnMetaInfo').innerHTML = `
    <span><i class="ri-box-3-line"></i> ${jumlahAlat} alat</span>
    <span><i class="ri-calendar-line"></i> Pinjam: ${tglPinjam}</span>
  `;
  
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
  if (!confirm(`Hapus data peminjaman "${kegiatan}"?\n\nData akan dipindahkan ke Trash di Notion.`)) return;
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
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ===== MODAL PENGEMBALIAN ===== -->
<div class="modal-overlay" id="returnModal" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-header-info">
        <div class="modal-icon-wrapper">
          <i class="ri-arrow-go-back-line"></i>
        </div>
        <div>
          <h3 class="modal-title" id="returnModalTitle">Konfirmasi Pengembalian</h3>
          <p class="modal-subtitle">Tandai alat sebagai sudah kembali</p>
        </div>
      </div>
      <button class="modal-close" onclick="closeReturnModal()" aria-label="Tutup modal" title="Tutup (Esc)">
        <i class="ri-close-line"></i>
      </button>
    </div>

    <form id="returnForm" onsubmit="submitReturn(event)" novalidate>
      <div class="modal-body">

        <!-- Info banner kegiatan -->
        <div class="return-info-banner" role="status">
          <div class="info-banner-icon">
            <i class="ri-calendar-event-fill"></i>
          </div>
          <div class="info-banner-content">
            <span class="info-banner-label">Kegiatan</span>
            <strong id="returnKegiatanLabel" class="info-banner-title">—</strong>
            <div class="info-banner-meta" id="returnMetaInfo">
              <!-- Diisi oleh JS -->
            </div>
          </div>
        </div>

        <!-- PIC Pengembali (wajib) -->
        <div class="r-form-group">
          <label class="r-form-label" for="fieldPic">
            <i class="ri-user-received-line"></i>
            Nama PIC Pengembali
            <span class="r-required" aria-hidden="true">*</span>
          </label>
          <div class="select-wrapper">
            <select id="fieldPic" name="nama_pic_pengembali" class="r-form-input r-form-select" required
                    aria-describedby="picHelp">
              <option value="" disabled selected>— Pilih petugas yang menerima —</option>
            </select>
            <i class="ri-arrow-down-s-line select-icon" aria-hidden="true"></i>
          </div>
          <span class="r-form-help" id="picHelp">Petugas yang menerima pengembalian alat</span>
          <span class="r-form-error" id="picError" role="alert">
            <i class="ri-error-warning-line"></i> PIC pengembali wajib dipilih
          </span>
        </div>

        <!-- Kondisi Alat Saat Kembali -->
        <div class="r-form-group">
          <label class="r-form-label" for="fieldKondisi">
            <i class="ri-tools-line"></i>
            Kondisi Alat Saat Kembali
          </label>
          <div class="select-wrapper">
            <select id="fieldKondisi" name="kondisi_alat" class="r-form-input r-form-select">
              <option value="" selected>— Pilih kondisi (opsional) —</option>
              <option value="Baik">✓ Baik — Tidak ada masalah</option>
              <option value="Rusak Ringan">⚠ Rusak Ringan — Masih bisa dipakai</option>
              <option value="Rusak Berat">✕ Rusak Berat — Perlu perbaikan</option>
              <option value="Hilang">⊘ Hilang — Tidak kembali</option>
            </select>
            <i class="ri-arrow-down-s-line select-icon" aria-hidden="true"></i>
          </div>
        </div>

        <!-- Catatan Pengembalian -->
        <div class="r-form-group">
          <div class="r-form-label-row">
            <label class="r-form-label" for="fieldCatatan">
              <i class="ri-sticky-note-line"></i>
              Catatan Pengembalian
            </label>
            <span class="char-counter" id="catatanCounter">0 / 300</span>
          </div>
          <textarea id="fieldCatatan" name="catatan_pengembalian" class="r-form-input r-form-textarea"
            rows="3" maxlength="300"
            placeholder="Tuliskan kondisi spesifik, nomor seri, atau catatan tambahan lainnya..."
            style="resize:vertical; min-height:80px;"></textarea>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeReturnModal()">
          <i class="ri-close-line"></i> Batal
        </button>
        <button type="submit" class="btn-modal-submit" id="returnSubmitBtn">
          <span class="btn-label" id="returnSubmitLabel">
            <i class="ri-checkbox-circle-line"></i> Konfirmasi Pengembalian
          </span>
          <span class="btn-loading" id="returnSubmitLoading" hidden>
            <i class="ri-loader-4-line ri-spin"></i> Memproses...
          </span>
        </button>
      </div>
    </form>
  </div>
</div>