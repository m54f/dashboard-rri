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
          <th>ID</th>
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
            <td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
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
            <td style="font-weight:700; color:var(--text-secondary);"><?= htmlspecialchars($c['id']) ?></td>
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
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deleteChecklist('<?= htmlspecialchars($c['page_id']) ?>', '<?= htmlspecialchars($c['tanggal']) ?> (<?= htmlspecialchars($c['checklist_type']) ?>)')">
                <i class="ri-delete-bin-line"></i>
              </button>
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
        <label>Petugas DP <span class="required-star">*</span> <span style="color:var(--text-secondary); font-weight:400; font-size:0.85rem;">— Pilih sebanyak yang Anda inginkan</span></label>
        <div class="checkbox-grid" id="field_petugas">
          <?php foreach ($petugasList as $nama): ?>
          <label class="checkbox-option">
            <input type="checkbox" name="petugas[]" value="<?= $nama ?>">
            <span class="checkbox-label"><?= $nama ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Keterangan Checklist -->
      <div class="form-group">
        <label>Keterangan Checklist</label>
        <textarea id="field_keterangan" name="keterangan" rows="3" placeholder="Catatan tambahan bila ada kendala..."></textarea>
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

  document.getElementById('modalTitle').textContent = 'Edit Checklist';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
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
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} checklist`;

    let buttonsHTML = '';
    buttonsHTML += `<button type="button" class="btn-page" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Prev</button>`;
    for (let p = 1; p <= totalPages; p++) {
      buttonsHTML += `<button type="button" class="btn-page ${p === currentPage ? 'active' : ''}" onclick="changePage(${p})">${p}</button>`;
    }
    buttonsHTML += `<button type="button" class="btn-page" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next &raquo;</button>`;
    paginationButtons.innerHTML = buttonsHTML;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  updateTablePagination();
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
  if (!confirm(`Hapus checklist "${label}"?\n\nData akan dipindahkan ke Trash di Notion.`)) return;
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
    color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.18); animation: slideInToast 0.4s ease;
    ${type === 'success' ? 'background: linear-gradient(135deg, #22c55e, #16a34a);' : 'background: linear-gradient(135deg, #ef4444, #dc2626);'}
  `;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; setTimeout(() => toast.remove(), 500); }, 4000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>