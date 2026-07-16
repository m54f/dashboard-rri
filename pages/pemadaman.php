<?php
require_once __DIR__.'/../api/pemadaman.php';

$pemadaman = getPemadaman();

if (!is_array($pemadaman) || isset($pemadaman['error'])) {
    $errorMessage = $pemadaman['error'] ?? 'Gagal memuat data';
    $pemadaman = [];
}

$total      = count($pemadaman);
$studioN    = count(array_filter($pemadaman, fn($p) => $p['pemancar'] === 'Pemancar Studio'));
$ngadirejoN = count(array_filter($pemadaman, fn($p) => $p['pemancar'] === 'Pemancar Ngadirejo'));
$ngijoN     = count(array_filter($pemadaman, fn($p) => $p['pemancar'] === 'Pemancar Ngijo'));
$probolinggoN = count(array_filter($pemadaman, fn($p) => $p['pemancar'] === 'Pemancar Probolinggo'));

$stats = [
    ['label' => 'Total Pemadaman', 'value' => $total, 'sub' => 'Data real-time dari Notion', 'icon' => 'ri-flashlight-line', 'tone' => 'orange'],
    ['label' => 'Pemancar Studio', 'value' => $studioN, 'sub' => 'Total kejadian', 'icon' => 'ri-broadcast-line', 'tone' => 'blue'],
    ['label' => 'Pemancar Ngadirejo', 'value' => $ngadirejoN, 'sub' => 'Total kejadian', 'icon' => 'ri-broadcast-line', 'tone' => 'purple'],
    ['label' => 'Pemancar Ngijo', 'value' => $ngijoN, 'sub' => 'Total kejadian', 'icon' => 'ri-broadcast-line', 'tone' => 'green'],
];

$base = '..';
$pageTitle = 'Log Pemadaman PLN';
$activeMenu = 'pemadaman';

$petugasList = ['Alfian','Alif','Saiful','Winsa','Yayang','Yhanuar','Yuda','Deny','Selvy','Anggi','Dedy','Syukur'];
$pemancarList = ['Pemancar Studio', 'Pemancar Ngijo', 'Pemancar Ngadirejo', 'Pemancar Probolinggo'];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h2>Log Pemadaman PLN</h2>
    <p>Catat dan pantau riwayat pemadaman listrik di setiap pemancar.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Catat Pemadaman</button>
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
  <div class="filter-tab active" onclick="filterPemadaman('Semua', this)">Semua</div>
  <div class="filter-tab" onclick="filterPemadaman('Pemancar Studio', this)">Studio</div>
  <div class="filter-tab" onclick="filterPemadaman('Pemancar Ngadirejo', this)">Ngadirejo</div>
  <div class="filter-tab" onclick="filterPemadaman('Pemancar Ngijo', this)">Ngijo</div>
  <div class="filter-tab" onclick="filterPemadaman('Pemancar Probolinggo', this)">Probolinggo</div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="ri-list-check-2"></i> Riwayat Pemadaman</div>
  </div>
  <div class="table-wrap">
    <table class="data-table" id="pemadamanTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tanggal & Waktu</th>
          <th>Pemancar</th>
          <th>Durasi</th>
          <th>Petugas</th>
          <th>Bukti Foto</th>
          <th>Waktu Submit</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pemadaman)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-secondary);">
              Tidak ada data pemadaman ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($pemadaman as $p):
            $editPayload = htmlspecialchars(json_encode([
                'page_id'      => $p['page_id'],
                'pemancar'     => $p['pemancar'],
                'tanggal_raw'  => $p['tanggal_raw'],
                'petugas'      => $p['petugas_arr'],
                'files'        => $p['files'] ?? [],
            ]), ENT_QUOTES, 'UTF-8');
            
            // Hitung durasi
            $durasi = '-';
            if (!empty($p['start_time']) && !empty($p['end_time'])) {
                $start = new DateTime($p['start_time']);
                $end = new DateTime($p['end_time']);
                $diff = $start->diff($end);
                if ($diff->h > 0) {
                    $durasi = $diff->h . 'j ' . $diff->i . 'm';
                } else {
                    $durasi = $diff->i . 'm';
                }
            }
          ?>
          <tr data-pemancar="<?= htmlspecialchars($p['pemancar']) ?>">
            <td style="font-weight:700; color:var(--text-secondary);"><?= htmlspecialchars($p['id']) ?></td>
            <td>
              <div style="font-weight:600;"><?= htmlspecialchars($p['tanggal']) ?></div>
              <div style="font-size:11px; color:var(--text-secondary);">
                <?= htmlspecialchars($p['start_time'] ?? '') ?> - <?= htmlspecialchars($p['end_time'] ?? '') ?>
              </div>
            </td>
            <td><span class="tag blue"><i class="ri-broadcast-line"></i> <?= htmlspecialchars($p['pemancar']) ?></span></td>
            <td><span class="mini-badge orange"><?= $durasi ?></span></td>
            <td><?= htmlspecialchars($p['petugas']) ?></td>
            <td>
              <?php if (!empty($p['files'])): ?>
                <span class="tag green"><i class="ri-image-line"></i> <?= count($p['files']) ?> file</span>
              <?php else: ?>
                <span style="font-size:11px; color:var(--text-secondary);">-</span>
              <?php endif; ?>
            </td>
            <td class="cell-sub"><?= htmlspecialchars($p['submission']) ?></td>
            <td class="cell-actions">
              <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
              </a>
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deletePemadaman('<?= htmlspecialchars($p['page_id']) ?>', '<?= htmlspecialchars($p['tanggal']) ?>')">
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

<!-- Modal Catat Pemadaman (Create & Edit) -->
<div class="modal-overlay" id="pemadamanModal">
  <div class="modal-content" style="max-width:600px;">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Catat Pemadaman PLN</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="pemadamanForm" onsubmit="handlePemadamanSubmit(event)">
      <input type="hidden" id="field_page_id" name="page_id" value="">

      <!-- Pemancar -->
      <div class="form-group">
        <label>Pemancar <span class="required-star">*</span></label>
        <div class="radio-group" id="field_pemancar">
          <?php foreach ($pemancarList as $loc): ?>
          <label class="radio-option">
            <input type="radio" name="pemancar" value="<?= htmlspecialchars($loc) ?>" required>
            <span class="radio-label"><i class="ri-broadcast-line"></i> <?= htmlspecialchars($loc) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tanggal & Waktu -->
      <div class="form-group">
        <label>Tanggal Pemadaman <span class="required-star">*</span></label>
        <input type="date" id="field_tanggal" name="tanggal" required>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Jam Mulai <span class="required-star">*</span></label>
          <input type="time" id="field_start_time" name="start_time" required>
        </div>
        <div class="form-group">
          <label>Jam Selesai <span class="required-star">*</span></label>
          <input type="time" id="field_end_time" name="end_time" required>
        </div>
      </div>

      <!-- Petugas -->
      <div class="form-group">
        <label>Petugas <span class="required-star">*</span> <span style="color:var(--text-secondary); font-weight:400; font-size:0.85rem;">— Pilih sebanyak yang Anda inginkan</span></label>
        <div class="checkbox-grid" id="field_petugas">
          <?php foreach ($petugasList as $nama): ?>
          <label class="checkbox-option">
            <input type="checkbox" name="petugas[]" value="<?= $nama ?>">
            <span class="checkbox-label"><?= $nama ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Upload Foto -->
      <div class="form-group">
        <label>Bukti Foto (Opsional)</label>
        <div style="background:var(--bg-secondary); border:2px dashed var(--border-color); border-radius:8px; padding:1.5rem; text-align:center;">
          <i class="ri-image-add-line" style="font-size:2rem; color:var(--text-secondary); margin-bottom:0.5rem;"></i>
          <div style="font-size:13px; color:var(--text-secondary); margin-bottom:0.5rem;">
            Upload foto kondisi pemadaman
          </div>
          <div style="font-size:11px; color:var(--text-secondary);">
            Maksimal 5 MB, 10 file
          </div>
          <input type="file" name="files[]" multiple accept="image/*" style="margin-top:0.5rem;">
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
function openModal(){ document.getElementById('pemadamanModal').classList.add('active'); }
function closeModal(){ document.getElementById('pemadamanModal').classList.remove('active'); }

document.getElementById('pemadamanModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ===== Mode CREATE =====
function openCreateModal(){
  const form = document.getElementById('pemadamanForm');
  form.reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Catat Pemadaman PLN';
  document.getElementById('submitBtnLabel').textContent = 'Kirim';
  // Default tanggal = hari ini
  document.getElementById('field_tanggal').value = new Date().toISOString().split('T')[0];
  
  // Set waktu default (opsional)
  const now = new Date();
  const timeStr = now.toTimeString().substring(0,5);
  document.getElementById('field_start_time').value = timeStr;
  
  openModal();
}

// ===== Mode EDIT =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('pemadamanForm');
  form.reset();

  document.getElementById('field_page_id').value = data.page_id;
  
  // Parse tanggal dari raw
  if (data.tanggal_raw) {
    const dateObj = new Date(data.tanggal_raw);
    document.getElementById('field_tanggal').value = dateObj.toISOString().split('T')[0];
  }
  
  const radio = form.querySelector(`input[name="pemancar"][value="${CSS.escape(data.pemancar)}"]`);
  if (radio) radio.checked = true;

  (data.petugas || []).forEach(nama => {
    const cb = form.querySelector(`input[name="petugas[]"][value="${CSS.escape(nama)}"]`);
    if (cb) cb.checked = true;
  });

  document.getElementById('modalTitle').textContent = 'Edit Pemadaman';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

// ===== STATE PAGINATION & FILTER =====
let currentPage = 1;
const rowsPerPage = 8;
let currentFilter = 'Semua';

function filterPemadaman(value, el){
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
  const allRows = Array.from(document.querySelectorAll('#pemadamanTable tbody tr'));
  const paginationWrap = document.getElementById('paginationWrap');

  if (allRows.length === 1 && allRows[0].querySelector('td[colspan]')) {
    if (paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  const filteredRows = allRows.filter(row => {
    if (currentFilter === 'Semua') return true;
    return row.dataset.pemancar === currentFilter;
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
    paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} pemadaman`;

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
async function handlePemadamanSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;

  const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
  if (petugasChecked.length === 0) { showToast('⚠️ Pilih minimal satu Petugas!', 'error'); return; }

  const pemancar = form.querySelector('input[name="pemancar"]:checked')?.value;
  if (!pemancar) { showToast('⚠️ Pilih lokasi Pemancar!', 'error'); return; }

  submitBtn.disabled = true;
  submitBtnLabel.textContent = 'Menyimpan...';

  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  try {
    const payload = {
      pemancar: pemancar,
      tanggal: form.tanggal.value,
      start_time: form.start_time.value,
      end_time: form.end_time.value,
      petugas: petugasChecked,
    };
    if (isEdit) payload.page_id = pageId;

    const endpoint = isEdit ? '../api/update-pemadaman.php' : '../api/create-pemadaman.php';
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
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (err) {
    console.error('Error:', err);
    showToast('❌ ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtnLabel.textContent = originalLabel;
  }
}

async function deletePemadaman(pageId, label) {
  if (!confirm(`Hapus catatan pemadaman "${label}"?\n\nData akan dipindahkan ke Trash di Notion.`)) return;
  try {
    const res = await fetch('../api/delete-pemadaman.php', {
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