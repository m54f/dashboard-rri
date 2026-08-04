<?php
require_once __DIR__.'/../api/checklist.php';

$checklist = getChecklist();

if (!is_array($checklist) || isset($checklist['error'])) {
    $errorMessage = $checklist['error'] ?? 'Gagal memuat data';
    $checklist = [];
}

$total    = count($checklist);
$pagiN    = count(array_filter($checklist, fn($c) => $c['checklist_type'] === 'Pagi'));
$malamN   = count(array_filter($checklist, fn($c) => $c['checklist_type'] === 'Malam'));
$lengkap  = count(array_filter($checklist, fn($c) => $c['is_complete']));
$belumN   = $total - $lengkap;

$stats = [
    ['label' => 'Total Checklist', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-list-check-3', 'tone' => 'blue'],
    ['label' => 'Sesi Pagi', 'value' => $pagiN, 'sub' => 'Checklist pagi', 'icon' => 'ri-sun-line', 'tone' => 'orange'],
    ['label' => 'Sesi Malam', 'value' => $malamN, 'sub' => 'Checklist malam', 'icon' => 'ri-moon-line', 'tone' => 'blue'],
    ['label' => 'Belum Lengkap', 'value' => $belumN, 'sub' => 'Butuh ditindaklanjuti', 'icon' => 'ri-error-warning-line', 'tone' => 'red'],
];

$base = '..';
$pageTitle = 'Checklist Pagi Malam';
$activeMenu = 'checklist';

$petugasList = ['Alfian','Alif','Saiful','Winsa','Yayang','Yhanuar','Yuda'];

include __DIR__ . '/../includes/header.php';
?>

<style>
  /* ===== Petugas DP — kartu pilih responsif ===== */
.petugas-head{
  display:flex; align-items:center; justify-content:space-between;
  gap:.5rem; flex-wrap:wrap; margin-bottom:.5rem;
}
.petugas-head > label{ margin-bottom:0; }
.petugas-hint{ color:var(--text-secondary); font-weight:400; font-size:.8rem; }
.petugas-tools{ display:flex; align-items:center; gap:.5rem; }
.petugas-count{
  font-size:.72rem; font-weight:700; color:#2563eb; background:#eff6ff;
  border:1px solid #bfdbfe; padding:.15rem .55rem; border-radius:999px;
  transition:all .2s ease;
}
.petugas-count.has-selection{ background:#2563eb; border-color:#2563eb; color:#fff; }
.petugas-toggle-all{
  border:none; background:transparent; color:#2563eb; cursor:pointer;
  font-size:.78rem; font-weight:600; padding:.2rem .45rem; border-radius:6px;
}
.petugas-toggle-all:hover{ background:#eff6ff; }

.petugas-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(96px, 1fr));
  gap:10px;
}
.petugas-option{ position:relative; cursor:pointer; -webkit-tap-highlight-color:transparent; }
.petugas-option input{ position:absolute; opacity:0; pointer-events:none; }

.petugas-card{
  position:relative;
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  gap:6px; min-height:88px; padding:12px 6px 10px;
  background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px;
  transition:border-color .18s, background .18s, box-shadow .18s, transform .12s;
  user-select:none; text-align:center;
}
.petugas-option:hover .petugas-card{ border-color:#cbd5e1; background:#f1f5f9; }
.petugas-option:active .petugas-card{ transform:scale(.96); }

.petugas-avatar{
  width:38px; height:38px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  background:#e2e8f0; color:#475569; font-weight:700; font-size:.95rem;
  transition:background .18s, color .18s;
}
.petugas-name{
  font-size:.82rem; font-weight:600; color:var(--text-secondary);
  max-width:100%; padding:0 4px;
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.petugas-check{
  position:absolute; top:6px; right:6px; width:18px; height:18px;
  border-radius:50%; background:#2563eb; color:#fff; font-size:12px;
  display:flex; align-items:center; justify-content:center;
  transform:scale(0); opacity:0;
  transition:transform .18s cubic-bezier(.34,1.56,.64,1), opacity .15s;
}

/* State terpilih */
.petugas-option input:checked + .petugas-card{
  border-color:#2563eb; background:#eff6ff;
  box-shadow:0 0 0 3px rgba(37,99,235,.12);
}
.petugas-option input:checked + .petugas-card .petugas-avatar{ background:#2563eb; color:#fff; }
.petugas-option input:checked + .petugas-card .petugas-name{ color:#1d4ed8; }
.petugas-option input:checked + .petugas-card .petugas-check{ transform:scale(1); opacity:1; }

/* Aksesibilitas keyboard */
.petugas-option input:focus-visible + .petugas-card{ outline:2px solid #2563eb; outline-offset:2px; }

/* Layar kecil (HP) */
@media (max-width:480px){
  .petugas-grid{ grid-template-columns:repeat(3, 1fr); gap:8px; }
  .petugas-card{ min-height:80px; }
  .petugas-avatar{ width:34px; height:34px; font-size:.9rem; }
  .petugas-name{ font-size:.76rem; }
}

/* Hilangkan tombol panah atas-bawah (spinner) pada input pagination */
.page-input-clean::-webkit-outer-spin-button,
.page-input-clean::-webkit-inner-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}
.page-input-clean {
  -moz-appearance: textfield !important;
  appearance: textfield !important;
}
</style>

<div class="page-head">
  <div>
    <h2>Checklist Pagi Malam</h2>
    <p>Rekap checklist perangkat siaran sesi Pagi dan Malam.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Isi Checklist Baru</button>
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
  <div class="filter-tab active" onclick="filterChecklist('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterChecklist('Pagi', this)">Pagi</div>
  <div class="filter-tab" onclick="filterChecklist('Malam', this)">Malam</div>
  <div class="filter-tab" onclick="filterChecklist('Belum Lengkap', this)">Belum Lengkap</div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-list-check-2"></i> Riwayat Checklist</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="checklistTable">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Sesi</th>
          <th>Kelengkapan</th>
          <th>Petugas DP</th>
          <th>Keterangan</th>
          <th>Waktu Submit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($checklist)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Tidak ada data checklist ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($checklist as $c):
            $editPayload = htmlspecialchars(json_encode([
                'page_id'    => $c['page_id'],
                'checklist'  => $c['checklist_type'],
                'tanggal'    => $c['tanggal_raw'],
                'pro1'       => $c['pro1'],
                'pro2'       => $c['pro2'],
                'pro4'       => $c['pro4'],
                'tetrack'    => $c['tetrack'],
                'mcr'        => $c['mcr'],
                'petugas'    => $c['petugas_arr'],
                'keterangan' => $c['keterangan'],
            ]), ENT_QUOTES, 'UTF-8');
          ?>
          <tr data-status="<?= htmlspecialchars($c['checklist_type']) ?>" data-complete="<?= $c['is_complete'] ? '1' : '0' ?>">
            <td class="cell-sub"><?= htmlspecialchars($c['tanggal']) ?></td>
            <td><span class="tag gray"><i class="<?= $c['checklist_type'] === 'Pagi' ? 'ri-sun-line' : 'ri-moon-line' ?>"></i> <?= htmlspecialchars($c['checklist_type']) ?></span></td>
            <td>
              <div style="display:flex;gap:4px;align-items:center;">
                <span class="mini-badge <?= $c['pro1'] ? 'on' : 'off' ?>" title="Programa 1">P1</span>
                <span class="mini-badge <?= $c['pro2'] ? 'on' : 'off' ?>" title="Programa 2">P2</span>
                <span class="mini-badge <?= $c['pro4'] ? 'on' : 'off' ?>" title="Programa 4">P4</span>
                <span class="mini-badge <?= $c['tetrack'] ? 'on' : 'off' ?>" title="Te-track">TT</span>
                <span class="mini-badge <?= $c['mcr'] ? 'on' : 'off' ?>" title="MCR Depan">MCR</span>
                <span style="font-size:11px;color:var(--text-secondary);margin-left:4px;"><?= $c['done_items'] ?>/<?= $c['total_items'] ?></span>
              </div>
            </td>
            <td><?= htmlspecialchars($c['petugas']) ?></td>
            <td class="cell-sub" style="max-width:220px;"><?= htmlspecialchars(mb_strimwidth($c['keterangan'], 0, 60, '...')) ?></td>
            <td class="cell-sub"><?= htmlspecialchars($c['submission']) ?></td>
            <td class="cell-actions">
              <a href="<?= htmlspecialchars($c['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
              </a>
              <?php if (getRole() === 'admin'): ?>
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deleteChecklist('<?= htmlspecialchars($c['page_id']) ?>', '<?= htmlspecialchars($c['tanggal']) ?> (<?= htmlspecialchars($c['checklist_type']) ?>)')">
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

<!-- Modal Isi Checklist (Create & Edit) -->
<div class="modal-overlay" id="checklistModal">
  <div class="modal-content" style="max-width:640px;">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Isi Checklist Pagi Malam</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="checklistForm" onsubmit="handleChecklistSubmit(event)">
      <div class="modal-body">
        <input type="hidden" id="field_page_id" name="page_id" value="">

        <div class="form-row">
          <!-- Sesi Checklist -->
          <div class="form-group">
            <label>Sesi Checklist <span class="required-star">*</span></label>
            <div class="radio-segment" id="field_checklist">
              <input type="radio" name="checklist" value="Pagi" id="r_pagi" required>
              <label for="r_pagi"><i class="ri-sun-line"></i> Pagi</label>
              <input type="radio" name="checklist" value="Malam" id="r_malam">
              <label for="r_malam"><i class="ri-moon-line"></i> Malam</label>
            </div>
          </div>

          <!-- Tanggal Cek -->
          <div class="form-group">
            <label>Tanggal Cek <span class="required-star">*</span></label>
            <input type="date" id="field_tanggal" name="tanggal" required>
          </div>
        </div>

        <div class="form-group">
          <label>Cek Perangkat <span style="font-weight:400; color:var(--text-secondary); font-size:11px;">(Klik kartu untuk menandai sudah dicek)</span></label>
          
          <div class="check-item" data-target="field_pro1" onclick="toggleCheckItem(this)">
            <div class="check-item-text">
              <h4>Programa 1 <span class="required-star">*</span></h4>
              <p>Cek Kondisi Mixer - PC Aircast (App Aircast bisa dibuka) - PC Dias (App DIAS terhubung) - PC Streaming - Telepon (WA dan Line pastikan terhubung) - AC | Jika Malam, Pastikan Perangkat Programa Sudah Off</p>
            </div>
            <div class="check-status">Belum Cek</div>
            <input type="checkbox" id="field_pro1" name="pro1" hidden>
          </div>

          <div class="check-item" data-target="field_pro2" onclick="toggleCheckItem(this)">
            <div class="check-item-text">
              <h4>Programa 2 <span class="required-star">*</span></h4>
              <p>Cek Kondisi Mixer - PC Aircast (App Aircast bisa dibuka) - PC Streaming - PC Backup - Telepon (WA dan Line pastikan terhubung) - AC | Jika Malam, Pastikan Perangkat Programa Sudah Off</p>
            </div>
            <div class="check-status">Belum Cek</div>
            <input type="checkbox" id="field_pro2" name="pro2" hidden>
          </div>

          <div class="check-item" data-target="field_pro4" onclick="toggleCheckItem(this)">
            <div class="check-item-text">
              <h4>Programa 4 <span class="required-star">*</span></h4>
              <p>Cek Kondisi Mixer - PC Aircast (App Aircast bisa dibuka) - PC Streaming - PC WA - Telepon (WA dan Line pastikan terhubung) - AC | Jika Malam, Pastikan Perangkat Programa Sudah Off</p>
            </div>
            <div class="check-status">Belum Cek</div>
            <input type="checkbox" id="field_pro4" name="pro4" hidden>
          </div>

          <div class="check-item" data-target="field_tetrack" onclick="toggleCheckItem(this)">
            <div class="check-item-text">
              <h4>Te-track</h4>
              <p>Monitoring log Pemancar melalui aplikasi Te-Track</p>
            </div>
            <div class="check-status">Belum Submit</div>
            <input type="checkbox" id="field_tetrack" name="tetrack" hidden>
          </div>

          <div class="check-item" data-target="field_mcr" onclick="toggleCheckItem(this)">
            <div class="check-item-text">
              <h4>MCR Depan <span class="required-star">*</span></h4>
              <p>Cek Kondisi PC Streaming dan Logger - PC Logger Back Up - Backup Rekaman Logger Harian</p>
            </div>
            <div class="check-status">Belum Cek</div>
            <input type="checkbox" id="field_mcr" name="mcr" hidden>
          </div>
        </div>

        <!-- Petugas DP -->
<div class="form-group">
  <div class="petugas-head">
    <label>Petugas DP <span class="required-star">*</span>
      <span class="petugas-hint">— pilih satu atau lebih</span>
    </label>
    <div class="petugas-tools">
      <span class="petugas-count" id="petugasCount">0 dipilih</span>
      <button type="button" class="petugas-toggle-all" id="petugasToggleAll" onclick="toggleAllPetugas()">Pilih semua</button>
    </div>
  </div>
                  <!-- Daftar Petugas DP -->
        <div class="petugas-grid" id="field_petugas">
          <?php foreach ($petugasList as $nama): ?>
          <label class="petugas-option">
            <input type="checkbox" name="petugas[]" value="<?= htmlspecialchars($nama) ?>">
            <span class="petugas-card">
              <span class="petugas-check"><i class="ri-check-line"></i></span>
              
              <span class="petugas-name"><?= htmlspecialchars($nama) ?></span>
            </span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

        <!-- Keterangan Checklist -->
        <div class="form-group">
          <label>Keterangan Checklist</label>
          <textarea id="field_keterangan" name="keterangan" rows="3" placeholder="Catatan tambahan bila ada kendala..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-send-plane-line"></i> <span id="submitBtnLabel">Kirim</span></button>
      </div>
    </form>
  </div>
</div>

<script>

function openModal(){ document.getElementById('checklistModal').classList.add('active'); }
function closeModal(){ document.getElementById('checklistModal').classList.remove('active'); }

document.getElementById('checklistModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ===== Fungsi Toggle Kartu Ceklis =====
function toggleCheckItem(el) {
  const inputId = el.getAttribute('data-target');
  const input = document.getElementById(inputId);
  const statusEl = el.querySelector('.check-status');
  
  input.checked = !input.checked;
  el.classList.toggle('checked', input.checked);
  
  // Ubah teks status
  if (inputId === 'field_tetrack') {
    statusEl.textContent = input.checked ? 'Sudah Submit' : 'Belum Submit';
  } else {
    statusEl.textContent = input.checked ? 'Sudah Cek' : 'Belum Cek';
  }
}

// Fungsi bantu untuk reset visual kartu saat buka modal
function resetCheckItemVisuals() {
  document.querySelectorAll('.check-item').forEach(el => {
    const inputId = el.getAttribute('data-target');
    const input = document.getElementById(inputId);
    const statusEl = el.querySelector('.check-status');
    
    el.classList.toggle('checked', input.checked);
    if (inputId === 'field_tetrack') {
      statusEl.textContent = input.checked ? 'Sudah Submit' : 'Belum Submit';
    } else {
      statusEl.textContent = input.checked ? 'Sudah Cek' : 'Belum Cek';
    }
  });
}

// ===== Mode CREATE =====
function openCreateModal(){
  const form = document.getElementById('checklistForm');
  form.reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Isi Checklist Pagi Malam';
  document.getElementById('submitBtnLabel').textContent = 'Kirim';
  document.getElementById('field_tanggal').value = new Date().toISOString().split('T')[0];
  resetCheckItemVisuals(); // Reset warna kartu
  updatePetugasCount();
  openModal();
}

// ===== Mode EDIT =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('checklistForm');
  form.reset();

  document.getElementById('field_page_id').value = data.page_id;
  document.getElementById('field_tanggal').value = data.tanggal || '';
  
  // Set nilai checkbox & update visual kartunya
  document.getElementById('field_pro1').checked = !!data.pro1;
  document.getElementById('field_pro2').checked = !!data.pro2;
  document.getElementById('field_pro4').checked = !!data.pro4;
  document.getElementById('field_tetrack').checked = !!data.tetrack;
  document.getElementById('field_mcr').checked = !!data.mcr;
  resetCheckItemVisuals(); // Terapkan warna hijau pada yang sudah dicek

  document.getElementById('field_keterangan').value = data.keterangan || '';

  const radio = form.querySelector(`input[name="checklist"][value="${CSS.escape(data.checklist)}"]`);
  if (radio) radio.checked = true;

  (data.petugas || []).forEach(nama => {
    const cb = form.querySelector(`input[name="petugas[]"][value="${CSS.escape(nama)}"]`);
    if (cb) cb.checked = true;
  });
  updatePetugasCount();

  document.getElementById('modalTitle').textContent = 'Edit Checklist';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

// ===== Petugas DP: counter & pilih semua =====
function updatePetugasCount(){
  const boxes  = document.querySelectorAll('#field_petugas input[name="petugas[]"]');
  const chosen = document.querySelectorAll('#field_petugas input[name="petugas[]"]:checked');
  const count  = document.getElementById('petugasCount');
  const btn    = document.getElementById('petugasToggleAll');
  if (!count) return;
  count.textContent = chosen.length + ' dipilih';
  count.classList.toggle('has-selection', chosen.length > 0);
  if (btn) btn.textContent = (boxes.length && chosen.length === boxes.length) ? 'Batal semua' : 'Pilih semua';
}

function toggleAllPetugas(){
  const boxes = document.querySelectorAll('#field_petugas input[name="petugas[]"]');
  const allChecked = [...boxes].every(b => b.checked);
  boxes.forEach(b => { b.checked = !allChecked; });
  updatePetugasCount();
}

// ===== STATE PAGINATION & FILTER =====
let currentPage = 1;
const rowsPerPage = 8;
let currentFilter = 'Semua';

function filterChecklist(value, el){
  if (el) {
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
  }
  currentFilter = value;
  currentPage = 1;
  updateTablePagination();
}

// Pagination dipadatkan supaya muat di layar HP (halaman pertama, halaman
// terakhir, dan beberapa halaman di sekitar halaman aktif — sisanya diwakili
// tanda "…"). Tanpa ini, kalau halaman banyak, semua nomor kelist penuh dan
// bikin harus scroll horizontal (Prev/Next jadi ketutup/kegeser ke luar layar).
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
  const allRows = Array.from(document.querySelectorAll('#checklistTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');

  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  const filteredRows = allRows.filter(row => {
    if (currentFilter === 'Semua') return true;
    if (currentFilter === 'Belum Lengkap') return row.dataset.complete === '0';
    return row.dataset.status === currentFilter;
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
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} checklist`;

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
  document.getElementById('field_petugas').addEventListener('change', updatePetugasCount);
  updatePetugasCount();
});

// ===== Submit (Create/Update) =====
async function handleChecklistSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;

  const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
  if (petugasChecked.length === 0) { showToast('⚠️ Pilih minimal satu Petugas DP!', 'error'); return; }

  const checklistType = form.querySelector('input[name="checklist"]:checked')?.value;
  if (!checklistType) { showToast('⚠️ Pilih Pagi atau Malam!', 'error'); return; }

  submitBtn.disabled = true;
  submitBtnLabel.textContent = 'Menyimpan...';

  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  try {
    const payload = {
      checklist: checklistType,
      tanggal: form.tanggal.value,
      pro1: form.pro1.checked,
      pro2: form.pro2.checked,
      pro4: form.pro4.checked,
      tetrack: form.tetrack.checked,
      mcr: form.mcr.checked,
      petugas: petugasChecked,
      keterangan: form.keterangan.value.trim(),
    };
    if (isEdit) payload.page_id = pageId;

    const endpoint = isEdit ? '../api/update-checklist.php' : '../api/create-checklist.php';
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
      let errMsg = data.error || 'Unknown error';
      if (data.details) {
          errMsg += '\nDetails: ' + (typeof data.details === 'string' ? data.details : JSON.stringify(data.details));
      }
      showToast('❌ Gagal: ' + errMsg, 'error');
      console.error('Notion Error Details:', data);
    }
  } catch (err) {
    console.error('Error:', err);
    showToast('❌ ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtnLabel.textContent = originalLabel;
  }
}

async function deleteChecklist(pageId, label) {
  customConfirm(
    'Hapus Checklist',
    `Hapus checklist "${label}"? Data akan dipindahkan ke Trash di Notion.`,
    async () => {
      try {
        const res = await fetch('../api/delete-checklist.php', {
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