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
 $alatList = ['Mixer', 'Mic', 'Laptop', 'Tripod', 'Kamera', 'Kabel', 'Soundcard', 'Speaker', 'HT', 'Headphone', 'Modem', 'Video Switcher', 'Video Sender', 'Converter', 'Baterai', 'Lampu', 'Overlength', 'Earpik', 'Stand Mic', 'PC Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Audio Distribution', 'Lainnya'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Dependencies untuk Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
          <td class="cell-sub"><?= htmlspecialchars($it['pic']) ?></td>
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
  <div class="modal-content" style="max-width:800px;">
    <div class="modal-header"><h3 class="modal-title" id="modalTitle">Catat Peminjaman Alat</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
    <form id="pinjamForm" onsubmit="handleSubmit(event)">
      <input type="hidden" id="field_page_id" value="">

      <div class="form-row">
        <div class="form-group">
          <label>Nama Kegiatan</label>
          <select id="field_kegiatan" required>
            <option value="">Pilih Kegiatan</option>
            <option value="Event Kantor">Event Kantor</option>
            <option value="Troubleshooting / Maintenance">Troubleshooting / Maintenance</option>
            <option value="Upacara / Apel Pagi">Upacara / Apel Pagi</option>
            <option value="Acara Live / Dialog Luar / Tapping">Acara Live / Dialog Luar / Tapping</option>
            <option value="Podcast / Tapping">Podcast / Tapping</option>
            <option value="Keroncong">Keroncong</option>
            <option value="Senandung Rindu">Senandung Rindu</option>
            <option value="Zoom Meeting">Zoom Meeting</option>
            <option value="Kunjungan">Kunjungan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Nama PIC PT</label>
          <select id="field_pic" multiple required>
            <?php foreach ($picList as $nama): ?>
              <option value="<?= $nama ?>"><?= $nama ?></option>
            <?php endforeach; ?>
          </select>
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
        <div class="form-group">
          <label>Lokasi</label>
          <input type="text" id="field_lokasi" placeholder="Contoh: Auditorium">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Durasi (jam)</label>
          <input type="number" id="field_durasi" min="0" step="0.5" placeholder="Contoh: 3">
        </div>
        <div class="form-group">
          <label>Keterangan</label>
          <input type="text" id="field_keterangan" placeholder="Catatan tambahan">
        </div>
      </div>

      <!-- Checkbox Alat Dinamis (Persis seperti GAS) -->
      <div class="form-group">
        <label>Alat yang Dipinjam <span class="required-star">*</span></label>
        <div id="alatContainer" class="alat-grid"></div>
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
  .alat-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:0.75rem; max-height:350px; overflow-y:auto; padding:0.5rem; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; }
  .alat-checkbox-item { background:#fff; padding:0.75rem; border-radius:8px; border:1px solid #e5e7eb; }
  .alat-cb-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem; }
  .alat-cb-top label { margin-left:0.5rem; font-weight:600; flex-grow:1; cursor:pointer; }
  .btn-add-alat { background:#3b82f6; color:#fff; border:none; border-radius:4px; width:24px; height:24px; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; }
  .btn-remove-alat { background:#ef4444; color:#fff; border:none; border-radius:4px; width:24px; height:24px; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; margin-left:auto; }
  .alat-cb-inputs { display:flex; flex-direction:column; gap:0.4rem; }
  .alat-cb-inputs input { padding:0.4rem 0.6rem; font-size:0.85rem; border:1px solid #d1d5db; border-radius:6px; width:100%; box-sizing:border-box; }
  .alat-cb-inputs input:disabled { background:#f3f4f6; cursor:not-allowed; }
  
  /* Select2 Z-index fix biar nampil di atas modal */
  .select2-container--open { z-index: 99999 !important; }
  .select2-container { width: 100% !important; margin-bottom: 0.5rem; }
  
  .cell-actions { display:flex; gap:0.4rem; align-items:center; }
  .icon-action.edit, .icon-action.return { background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.edit { color:#3b82f6; }
  .icon-action.edit:hover { background:rgba(59,130,246,0.12); }
  .icon-action.return { color:#22c55e; }
  .icon-action.return:hover { background:rgba(34,197,94,0.12); }
  .icon-action.delete { color:#ef4444; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.delete:hover { background:rgba(239,68,68,0.12); }
  @media (max-width:600px) { .alat-grid { grid-template-columns: 1fr; } }
</style>

<script>
const ALAT_LIST = <?= json_encode($alatList) ?>;

// Mapping Alat Default Berdasarkan Kegiatan
const alatPerKegiatan = {
  "Upacara / Apel Pagi": [
    { nama: "Mic", jenis: "Kabel", qty: 3 }, { nama: "Speaker", jenis: "Huper", qty: 1 },
    { nama: "Overlength", jenis: "Putih", qty: 1 }, { nama: "Mixer", jenis: "8 chanel", qty: 1 },
    { nama: "Stand Mic", jenis: "Mic", qty: 3 }, { nama: "Kabel", jenis: "Power", qty: 2 },
    { nama: "Kabel", jenis: "Roll Biru", qty: 1 }, { nama: "Kabel", jenis: "Male Female", qty: 4 }
  ],
  "Acara Live / Dialog Luar / Tapping": [
    { nama: "Mic", jenis: "Kabel", qty: 3 }, { nama: "Kamera", jenis: "Mirrorless", qty: 2 },
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 3 },
    { nama: "Kabel", jenis: "HDMI", qty: 2 }, { nama: "Kabel", jenis: "SDI", qty: 1 },
    { nama: "Kabel", jenis: "Male Female", qty: 3 }, { nama: "Kabel", jenis: "Roll Biru", qty: 1 },
    { nama: "Video Switcher", jenis: "Avmatrik", qty: 1 }, { nama: "Soundcard", jenis: "Behringer", qty: 1 },
    { nama: "Baterai", jenis: "Dummy Kingma", qty: 1 }, { nama: "Converter", jenis: "HDMI-SDI", qty: 2 },
    { nama: "Mixer", jenis: "Digital Ashley", qty: 1 }, { nama: "Headphone", jenis: "Kabel", qty: 1 },
    { nama: "Laptop", jenis: "TUF", qty: 1 }, { nama: "Modem", jenis: "Orbit", qty: 1 },
    { nama: "Lampu", jenis: "Inbex", qty: 2 }, { nama: "Lampu", jenis: "Godox", qty: 1 },
    { nama: "Overlength", jenis: "MCB", qty: 1 }, { nama: "HT", jenis: "WLN", qty: 6 },
    { nama: "Earpik", jenis: "HT", qty: 6 }
  ],
  "Podcast / Tapping": [
    { nama: "Mic", jenis: "Saramonic Sepaket", qty: 1 }, { nama: "Kamera", jenis: "Mirrorless", qty: 2 },
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 3 },
    { nama: "Kabel", jenis: "HDMI", qty: 2 }, { nama: "Kabel", jenis: "SDI", qty: 1 },
    { nama: "Overlength", jenis: "putih", qty: 2 }, { nama: "Video Switcher", jenis: "Avmatrik", qty: 1 },
    { nama: "Soundcard", jenis: "Behringer", qty: 1 }, { nama: "Baterai", jenis: "Dummy Kingma", qty: 1 },
    { nama: "Laptop", jenis: "TUF", qty: 1 }, { nama: "Lampu", jenis: "Inbex", qty: 2 },
    { nama: "Lampu", jenis: "Godox", qty: 1 }
  ],
  "Keroncong": [
    { nama: "Kamera", jenis: "PXW", qty: 1 }, { nama: "Tripod", jenis: "kamera", qty: 1 },
    { nama: "Mic", jenis: "Wireless", qty: 1 }, { nama: "Mic", jenis: "Kabel", qty: 1 },
    { nama: "HT", jenis: "WLN", qty: 2 }, { nama: "Kabel", jenis: "Male Female", qty: 6 },
    { nama: "Kabel", jenis: "Male TS", qty: 5 }, { nama: "Overlength", jenis: "putih", qty: 1 },
    { nama: "Headphone", jenis: "Senheizer", qty: 1 }
  ],
  "Senandung Rindu": [
    { nama: "Kamera", jenis: "MD", qty: 1 }, { nama: "Kamera", jenis: "PXW", qty: 1 },
    { nama: "Tripod", jenis: "kamera", qty: 2 }, { nama: "Mic", jenis: "Wireless", qty: 1 },
    { nama: "Mic", jenis: "Kabel", qty: 1 }, { nama: "HT", jenis: "WLN", qty: 2 },
    { nama: "Kabel", jenis: "Male TS", qty: 1 }, { nama: "Overlength", jenis: "putih", qty: 2 },
    { nama: "Kabel", jenis: "Roll Biru", qty: 1 }
  ],
  "Zoom Meeting": [
    { nama: "Laptop", jenis: "MSI", qty: 1 }
  ],
  "Kunjungan": [
    { nama: "Laptop", jenis: "MSI", qty: 1 }, { nama: "Baterai", jenis: "Charge", qty: 4 }
  ]
};

// ===== Init Select2 & Checkbox =====
 $(document).ready(function() {
  $('#field_pic').select2({
    placeholder: "Pilih PIC PT",
    allowClear: true,
    width: '100%',
    closeOnSelect: false,
    dropdownParent: $('#pinjamModal')
  });

  $('#field_kegiatan').select2({
    placeholder: "Pilih Kegiatan",
    allowClear: true,
    width: '100%',
    dropdownParent: $('#pinjamModal')
  });

  $('#field_kegiatan').on('change', function() {
    updateAlatCheckboxes(this.value);
  });

  generateAlatCheckboxes();
  initCheckboxListeners();
});

function generateAlatCheckboxes() {
  const container = document.getElementById('alatContainer');
  container.innerHTML = '';
  ALAT_LIST.forEach((alat, index) => {
    const id = 'alat' + index;
    const div = document.createElement('div');
    div.className = 'alat-checkbox-item';
    div.setAttribute('data-alat', alat);
    div.innerHTML = `
      <div class="alat-cb-top">
        <input class="form-check-input alat-checkbox" type="checkbox" id="${id}" value="${alat}">
        <label for="${id}">${alat}</label>
        <button type="button" class="btn-add-alat" data-alat="${alat}">+</button>
      </div>
      <div class="alat-cb-inputs">
        <input type="text" class="alat-jenis" id="${id}_jenis" placeholder="Jenis/Tipe" disabled>
        <input type="number" min="1" max="9" class="alat-qty" id="${id}_qty" placeholder="Qty" disabled>
      </div>`;
    container.appendChild(div);
  });
}

function updateAlatCheckboxes(selectedEvent) {
  const defaultAlat = alatPerKegiatan[selectedEvent] || [];
  // Uncheck & disable semua dulu
  document.querySelectorAll('.alat-checkbox-item').forEach(item => {
    if (item.querySelector('.btn-remove-alat')) item.remove(); // Hapus duplikat
    const cb = item.querySelector('.alat-checkbox');
    if(cb) {
      cb.checked = false;
      const jenis = document.getElementById(cb.id + '_jenis');
      const qty = document.getElementById(cb.id + '_qty');
      if(jenis) { jenis.disabled = true; jenis.value = ''; }
      if(qty) { qty.disabled = true; qty.value = ''; }
    }
  });

  // Check & isi default
  defaultAlat.forEach(d => {
    let cb = [...document.querySelectorAll('.alat-checkbox:not([id*="dup"])')].find(x => x.value === d.nama);
    if (cb && !cb.checked) {
      cb.checked = true;
      const jenis = document.getElementById(cb.id + '_jenis');
      const qty = document.getElementById(cb.id + '_qty');
      if(jenis) { jenis.disabled = false; jenis.value = d.jenis; }
      if(qty) { qty.disabled = false; qty.value = d.qty; }
    } else if (cb && cb.checked) {
      // Jika sudah ada yang tercentang dengan nama sama, tambah duplikat
      addDuplicateRow(d.nama, d.jenis, d.qty);
    }
  });
}

function initCheckboxListeners() {
  const container = document.getElementById('alatContainer');
  container.addEventListener('change', e => {
    if (e.target.classList.contains('alat-checkbox')) {
      const id = e.target.id;
      const jenis = document.getElementById(id + '_jenis');
      const qty = document.getElementById(id + '_qty');
      const checked = e.target.checked;
      if(jenis) { jenis.disabled = !checked; if(!checked) jenis.value=''; }
      if(qty) { qty.disabled = !checked; if(!checked) qty.value=''; }
    }
  });

  container.addEventListener('click', e => {
    if (e.target.classList.contains('btn-add-alat')) {
      const alat = e.target.dataset.alat;
      addDuplicateRow(alat, '', '');
    } else if (e.target.classList.contains('btn-remove-alat')) {
      e.target.closest('.alat-checkbox-item').remove();
    }
  });
}

function addDuplicateRow(alat, jenisVal, qtyVal) {
  const newIndex = 'dup' + Date.now();
  const div = document.createElement('div');
  div.className = 'alat-checkbox-item';
  div.innerHTML = `
    <div class="alat-cb-top">
      <input class="form-check-input alat-checkbox" type="checkbox" id="${newIndex}" value="${alat}" checked>
      <label for="${newIndex}">${alat}</label>
      <button type="button" class="btn-remove-alat">×</button>
    </div>
    <div class="alat-cb-inputs">
      <input type="text" class="alat-jenis" id="${newIndex}_jenis" placeholder="Jenis/Tipe" value="${jenisVal}">
      <input type="number" min="1" max="9" class="alat-qty" id="${newIndex}_qty" placeholder="Qty" value="${qtyVal}">
    </div>`;
  document.getElementById('alatContainer').appendChild(div);
}

function collectAlatRows() {
  let rows = [];
  document.querySelectorAll('.alat-checkbox-item').forEach(item => {
    const cb = item.querySelector('.alat-checkbox');
    if (cb && cb.checked) {
      rows.push({
        alat: cb.value,
        jenis: item.querySelector('.alat-jenis').value.trim(),
        qty: item.querySelector('.alat-qty').value
      });
    }
  });
  return rows;
}

// ===== Modal Logic =====
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

function openCreateModal(){
  document.getElementById('pinjamForm').reset();
  document.getElementById('field_page_id').value = '';
  $('#field_pic').val(null).trigger('change');
  $('#field_kegiatan').val(null).trigger('change');
  document.getElementById('modalTitle').textContent = 'Catat Peminjaman Alat';
  document.getElementById('submitBtnLabel').textContent = 'Simpan';
  updateAlatCheckboxes(''); // Clear alat
  openModal();
}

function openEditModal(btn){
  const d = JSON.parse(btn.dataset.payload);
  document.getElementById('pinjamForm').reset();
  document.getElementById('field_page_id').value = d.page_id;
  document.getElementById('field_lokasi').value = d.lokasi || '';
  document.getElementById('field_tgl_kegiatan').value = d.tgl_kegiatan || '';
  document.getElementById('field_tgl_pinjam').value = d.tgl_pinjam || '';
  document.getElementById('field_durasi').value = d.durasi ?? '';
  document.getElementById('field_keterangan').value = d.keterangan || '';
  
  // Set Kegiatan & PIC
  $('#field_kegiatan').val(d.kegiatan).trigger('change');
  const picArr = d.pic ? d.pic.split(',').map(p => p.trim()) : [];
  $('#field_pic').val(picArr).trigger('change');

  // Reset & Set Alat
  updateAlatCheckboxes(''); // Uncheck all dulu
  if (d.alat_arr && d.alat_arr.length > 0) {
    d.alat_arr.forEach(a => {
      let cb = [...document.querySelectorAll('.alat-checkbox')].find(x => x.value === a.alat && !x.checked);
      if (cb) {
        cb.checked = true;
        const jenis = document.getElementById(cb.id + '_jenis');
        const qty = document.getElementById(cb.id + '_qty');
        if(jenis) { jenis.disabled = false; jenis.value = a.jenis; }
        if(qty) { qty.disabled = false; qty.value = a.qty; }
      } else {
        // Jika gk ada slot kosong, tambah duplicate
        addDuplicateRow(a.alat, a.jenis, a.qty);
      }
    });
  }

  document.getElementById('modalTitle').textContent = 'Edit Peminjaman Alat';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

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

  // PIC di join jadi string karena Notion API (Select) hanya terima 1 opsi, 
  // kecuali jika Anda merubahnya jadi Multi-select di Notion, maka kirim array picArr = $('#field_pic').val()
  const picArr = $('#field_pic').val();
  const picString = picArr ? picArr.join(', ') : '';

  const payload = {
    kegiatan: document.getElementById('field_kegiatan').value.trim(),
    pic: picString,
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