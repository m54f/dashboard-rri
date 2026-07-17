<?php
require_once __DIR__.'/../api/peminjaman.php';

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

$base = '..'; $pageTitle = 'Peminjaman Alat'; $activeMenu = 'peminjaman';

// Master data untuk dropdown
$picList  = ['Alfian','Alif','Saiful','Yhanuar','Yayang','Yudha','Dicky','Yoyok','Anggi','Ludfi','Shofirin','Syukur','Dedy','Deny','Dayat','Winsa','Selvy'];
$alatList = ['Mixer','Mic','Laptop','Tripod','Kamera','Kabel','Soundcard','Speaker','HT','Headphone','Modem','Video Switcher','Video Sender','Converter','Baterai','Lampu','Overlength','Earpik','Lainnya'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h2>Peminjaman Alat</h2>
    <p>Kelola peminjaman dan pengembalian alat siaran.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Catat Peminjaman</button>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
    ⚠️ Gagal memuat data dari Notion: <?= htmlspecialchars($errorMessage) ?>
  </div>
<?php endif; ?>

<div class="stats-row">
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-info"><h3><?= htmlspecialchars($s['label']) ?></h3><div class="stat-value"><?= htmlspecialchars($s['value']) ?></div><div class="stat-sub"><?= htmlspecialchars($s['sub']) ?></div></div>
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
  <div class="card-header"><div class="card-title"><i class="ri-service-line"></i> Riwayat Peminjaman</div></div>
  <div class="table-wrap">
    <table class="data-table" id="pinjamTable">
      <thead><tr><th>Kegiatan</th><th>PIC</th><th>Alat</th><th>Lokasi</th><th>Tgl Pinjam</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-secondary);">Tidak ada data peminjaman.</td></tr>
        <?php else: foreach ($items as $it):
          $editPayload = htmlspecialchars(json_encode([
              'page_id'      => $it['page_id'],
              'kegiatan'     => $it['kegiatan'],
              'pic'          => $it['pic'],
              'lokasi'       => $it['lokasi'],
              'tgl_kegiatan' => $it['tgl_kegiatan'],
              'tgl_pinjam'   => $it['tgl_pinjam'],
              'durasi'       => $it['durasi'],
              'keterangan'   => $it['keterangan'],
              'detail_alat'  => $it['detail_alat'],
              'alat_arr'     => $it['alat_arr'],
          ]), ENT_QUOTES, 'UTF-8');
        ?>
        <tr data-status="<?= htmlspecialchars($it['status']) ?>">
          <td class="cell-title"><?= htmlspecialchars($it['kegiatan']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['alat_display']) ?></td>
          <td class="cell-sub"><?= htmlspecialchars($it['lokasi']) ?></td>
          <td><?= htmlspecialchars($it['tgl_pinjam'] ?? '-') ?></td>
          <td><span class="status-tag <?= $it['cls'] ?>"><?= htmlspecialchars($it['status']) ?></span></td>
          <td class="cell-actions">
            <a href="<?= htmlspecialchars($it['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion"><i class="ri-external-link-line"></i></a>
            <?php if ($it['status'] === 'Dipinjam' || $it['status'] === 'Terlambat'): ?>
            <button type="button" class="icon-action return" title="Tandai Dikembalikan" onclick="markReturned('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>')"><i class="ri-checkbox-circle-line"></i></button>
            <?php endif; ?>
            <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)"><i class="ri-pencil-line"></i></button>
            <button type="button" class="icon-action delete" title="Hapus" onclick="deleteItem('<?= htmlspecialchars($it['page_id']) ?>', '<?= htmlspecialchars(addslashes($it['kegiatan'])) ?>')"><i class="ri-delete-bin-line"></i></button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal-overlay" id="pinjamModal">
  <div class="modal-content" style="max-width:640px;">
    <div class="modal-header"><h3 class="modal-title" id="modalTitle">Catat Peminjaman Alat</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form id="pinjamForm" onsubmit="handleSubmit(event)">
      <input type="hidden" id="field_page_id" value="">

      <div class="form-group">
        <label>Nama Kegiatan</label>
        <input type="text" id="field_kegiatan" placeholder="Contoh: Liputan Senam Sore RRI" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Nama PIC PT</label>
          <select id="field_pic" required>
            <option value="">Pilih PIC</option>
            <?php foreach ($picList as $nama): ?>
              <option value="<?= $nama ?>"><?= $nama ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" id="field_lokasi" placeholder="Contoh: Studio A">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tanggal Kegiatan</label>
          <input type="date" id="field_tgl_kegiatan">
        </div>
        <div class="form-group">
          <label>Tanggal Pinjam</label>
          <input type="date" id="field_tgl_pinjam" required>
        </div>
      </div>

      <div class="form-group">
        <label>Durasi (jam)</label>
        <input type="number" id="field_durasi" min="0" step="0.5" placeholder="Contoh: 3">
      </div>

      <!-- Daftar Alat Dinamis -->
      <div class="form-group">
        <label>Alat yang Dipinjam <span class="required-star">*</span></label>
        <div id="alatRows"></div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addAlatRow()" style="margin-top:0.5rem;">
          <i class="ri-add-line"></i> Tambah Alat
        </button>
      </div>

      <div class="form-group">
        <label>Keterangan</label>
        <textarea id="field_keterangan" rows="2" placeholder="Catatan tambahan (opsional)"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn"><span id="submitBtnLabel">Simpan</span></button>
      </div>
    </form>
  </div>
</div>

<style>
  .required-star { color:#ef4444; }
  .alat-row { display:grid; grid-template-columns: 1.2fr 1.2fr 0.6fr auto; gap:0.5rem; margin-bottom:0.5rem; align-items:center; }
  .alat-row select, .alat-row input { width:100%; }
  .alat-row .remove-row { background:none; border:none; color:#ef4444; cursor:pointer; font-size:1.1rem; padding:0.3rem; }
  .btn-sm { padding:0.4rem 0.8rem; font-size:0.85rem; }
  .cell-actions { display:flex; gap:0.4rem; align-items:center; }
  .icon-action.edit, .icon-action.return { background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.edit { color:#3b82f6; }
  .icon-action.edit:hover { background:rgba(59,130,246,0.12); }
  .icon-action.return { color:#22c55e; }
  .icon-action.return:hover { background:rgba(34,197,94,0.12); }
  .icon-action.delete { color:#ef4444; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.delete:hover { background:rgba(239,68,68,0.12); }
  @media (max-width:600px) { .alat-row { grid-template-columns: 1fr; } }
</style>

<script>
const ALAT_LIST = <?= json_encode($alatList) ?>;

function openModal(){ document.getElementById('pinjamModal').classList.add('active'); }
function closeModal(){ document.getElementById('pinjamModal').classList.remove('active'); }
document.getElementById('pinjamModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function filterPinjam(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#pinjamTable tbody tr').forEach(row => {
    row.style.display = (status === 'Semua' || row.dataset.status === status) ? '' : 'none';
  });
}

// ===== Baris alat dinamis =====
function alatRowTemplate(alat = '', jenis = '', qty = '') {
  const options = ALAT_LIST.map(a => `<option value="${a}" ${a === alat ? 'selected' : ''}>${a}</option>`).join('');
  return `
    <div class="alat-row">
      <select class="alat-nama">
        <option value="">Pilih Alat</option>
        ${options}
      </select>
      <input type="text" class="alat-jenis" placeholder="Jenis/Tipe" value="${jenis}">
      <input type="number" class="alat-qty" placeholder="Qty" min="1" value="${qty}">
      <button type="button" class="remove-row" onclick="this.closest('.alat-row').remove()"><i class="ri-close-line"></i></button>
    </div>`;
}

function addAlatRow(alat = '', jenis = '', qty = '') {
  document.getElementById('alatRows').insertAdjacentHTML('beforeend', alatRowTemplate(alat, jenis, qty));
}

function resetAlatRows() {
  document.getElementById('alatRows').innerHTML = '';
  addAlatRow(); // selalu mulai dengan 1 baris kosong
}

function collectAlatRows() {
  return [...document.querySelectorAll('.alat-row')].map(row => ({
    alat: row.querySelector('.alat-nama').value,
    jenis: row.querySelector('.alat-jenis').value.trim(),
    qty: row.querySelector('.alat-qty').value
  })).filter(r => r.alat);
}

// ===== Mode Create =====
function openCreateModal(){
  document.getElementById('pinjamForm').reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Catat Peminjaman Alat';
  document.getElementById('submitBtnLabel').textContent = 'Simpan';
  resetAlatRows();
  openModal();
}

// ===== Mode Edit =====
function openEditModal(btn){
  const d = JSON.parse(btn.dataset.payload);
  document.getElementById('pinjamForm').reset();

  document.getElementById('field_page_id').value = d.page_id;
  document.getElementById('field_kegiatan').value = d.kegiatan || '';
  document.getElementById('field_pic').value = d.pic || '';
  document.getElementById('field_lokasi').value = d.lokasi || '';
  document.getElementById('field_tgl_kegiatan').value = d.tgl_kegiatan || '';
  document.getElementById('field_tgl_pinjam').value = d.tgl_pinjam || '';
  document.getElementById('field_durasi').value = d.durasi ?? '';
  document.getElementById('field_keterangan').value = d.keterangan || '';

  document.getElementById('alatRows').innerHTML = '';
  if (d.alat_arr && d.alat_arr.length > 0) {
    d.alat_arr.forEach(a => addAlatRow(a));
  } else {
    addAlatRow();
  }

  document.getElementById('modalTitle').textContent = 'Edit Peminjaman Alat';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

// ===== Submit (create / update) =====
async function handleSubmit(event) {
  event.preventDefault();

  const alatRows = collectAlatRows();
  if (alatRows.length === 0) {
    showToast('⚠️ Pilih minimal satu alat!', 'error');
    return;
  }

  const submitBtn = document.getElementById('submitBtn');
  const label = document.getElementById('submitBtnLabel');
  const originalLabel = label.textContent;
  submitBtn.disabled = true;
  label.textContent = 'Menyimpan...';

  const pageId = document.getElementById('field_page_id').value;
  const isEdit = !!pageId;

  const payload = {
    kegiatan: document.getElementById('field_kegiatan').value.trim(),
    pic: document.getElementById('field_pic').value,
    lokasi: document.getElementById('field_lokasi').value.trim(),
    tgl_kegiatan: document.getElementById('field_tgl_kegiatan').value,
    tgl_pinjam: document.getElementById('field_tgl_pinjam').value,
    durasi: document.getElementById('field_durasi').value,
    keterangan: document.getElementById('field_keterangan').value.trim(),
    alat: alatRows
  };
  if (isEdit) payload.page_id = pageId;

  const endpoint = isEdit ? '../api/update-peminjaman.php' : '../api/create-peminjaman.php';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      showToast('✅ ' + data.message, 'success');
      closeModal();
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (err) {
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    label.textContent = originalLabel;
  }
}

// ===== Tandai Dikembalikan =====
async function markReturned(pageId, kegiatan) {
  if (!confirm(`Tandai "${kegiatan}" sebagai sudah dikembalikan?`)) return;
  try {
    const res = await fetch('../api/mark-returned.php', {
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

// ===== Delete =====
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

function showToast(message, type) {
  const existing = document.querySelector('.toast-notification');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = 'toast-notification ' + type;
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
    padding: 1rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem;
    color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    animation: slideInToast 0.4s ease;
    ${type === 'success' ? 'background: linear-gradient(135deg, #22c55e, #16a34a);' : 'background: linear-gradient(135deg, #ef4444, #dc2626);'}
  `;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; setTimeout(() => toast.remove(), 500); }, 4000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>