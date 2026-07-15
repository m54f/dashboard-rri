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

<div class="page-head">
  <div>
    <h2>Troubleshoot</h2>
    <p>Catatan dan status penanganan kendala teknis siaran.</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()"><i class="ri-add-line"></i> Lapor Kendala Baru</button>
</div>

<!-- Tampilkan pesan error jika API gagal (Opsional, untuk debugging) -->
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
          <th>ID</th>
          <th>Judul Kendala</th>
          <th>Kategori</th>
          <th>PIC</th>
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
            // Payload lengkap buat pre-fill modal edit, di-escape aman utk atribut HTML
            $editPayload = htmlspecialchars(json_encode([
                'page_id'    => $t['page_id'],
                'kendala'    => $t['title'],
                'bidang'     => $t['bidang_raw'],
                'deskripsi'  => $t['deskripsi'],
                'kesimpulan' => $t['kesimpulan'],
                'petugas'    => $t['petugas_arr'],
            ]), ENT_QUOTES, 'UTF-8');
          ?>
          <tr data-status="<?= htmlspecialchars($t['status']) ?>">
            <td style="font-weight:700; color:var(--text-secondary);"><?= htmlspecialchars($t['id']) ?></td>
            <td class="cell-title"><?= htmlspecialchars($t['title']) ?></td>
            <td><span class="tag gray"><?= htmlspecialchars($t['cat']) ?></span></td>
            <td>
              <span class="avatar-sm"><?= strtoupper(substr($t['pic'], 0, 2)) ?></span> 
              <?= htmlspecialchars($t['pic']) ?>
            </td>
            <td><span class="status-tag <?= htmlspecialchars($t['cls']) ?>"><?= htmlspecialchars($t['status']) ?></span></td>
            <td class="cell-sub"><?= htmlspecialchars($t['date']) ?></td>
            <td class="cell-actions">
              <a href="<?= htmlspecialchars($t['url']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
                <i class="ri-external-link-line"></i>
              </a>
              <button type="button" class="icon-action edit" title="Edit" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
                <i class="ri-edit-line"></i>
              </button>
              <button type="button" class="icon-action delete" title="Hapus" onclick="deleteTicket('<?= htmlspecialchars($t['page_id']) ?>', '<?= htmlspecialchars(addslashes($t['title'])) ?>')">
                <i class="ri-delete-bin-line"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Lapor Kendala (dipakai ulang untuk Create & Edit) -->
<div class="modal-overlay" id="ticketModal">
  <div class="modal-content" style="max-width:600px;">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Formulir Pencatatan Troubleshoot</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="newTicketForm" onsubmit="handleNewTicket(event)">
      <!-- Hidden field: kosong = mode Create, terisi = mode Edit -->
      <input type="hidden" id="field_page_id" name="page_id" value="">

      <!-- Kendala (Title) -->
      <div class="form-group">
        <label>Kendala <span style="color:var(--text-secondary); font-weight:400; font-size:0.85rem;">— Singkat dan Jelas</span></label>
        <input type="text" id="field_kendala" name="kendala" placeholder="Contoh: Internet studio terputus" required>
      </div>

      <!-- Bidang (Multi Select - tapi pilih satu via radio) -->
      <div class="form-group">
        <label>Bidang <span class="required-star">*</span></label>
        <div class="radio-group" id="field_bidang">
          <label class="radio-option">
            <input type="radio" name="bidang" value="Sarana Prasarana" required>
            <span class="radio-label">Sarana Prasarana</span>
          </label>
          <label class="radio-option">
            <input type="radio" name="bidang" value="Pemancar">
            <span class="radio-label">Pemancar</span>
          </label>
          <label class="radio-option">
            <input type="radio" name="bidang" value="Studio">
            <span class="radio-label">Studio</span>
          </label>
        </div>
      </div>

      <!-- Deskripsi Aktivitas Identifikasi (Rich Text) -->
      <div class="form-group">
        <label>Deskripsi Aktivitas Identifikasi</label>
        <textarea id="field_deskripsi" name="deskripsi" rows="3" placeholder="Jelaskan kronologi & gejala masalah..."></textarea>
      </div>

      <!-- Kesimpulan Penyelesaian (Rich Text) -->
      <div class="form-group">
        <label>Kesimpulan Penyelesaian</label>
        <input type="text" id="field_kesimpulan" name="kesimpulan" placeholder="Bila sudah ada penyelesaian...">
      </div>

      <!-- Nama Petugas (Multi Select - checkbox) -->
      <div class="form-group">
        <label>Nama Petugas <span class="required-star">*</span> <span style="color:var(--text-secondary); font-weight:400; font-size:0.85rem;">— Pilih sebanyak yang Anda inginkan</span></label>
        <div class="checkbox-grid" id="field_petugas">
          <?php foreach ($petugasList as $nama): ?>
          <label class="checkbox-option">
            <input type="checkbox" name="petugas[]" value="<?= $nama ?>">
            <span class="checkbox-label"><?= $nama ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn"><i class="ri-send-plane-line"></i> <span id="submitBtnLabel">Kirim Laporan</span></button>
      </div>
    </form>
  </div>
</div>

<style>
  .required-star { color: #ef4444; }
  .radio-group { display:flex; flex-direction:column; gap:0.5rem; margin-top:0.4rem; }
  .radio-option, .checkbox-option {
    display:flex; align-items:center; gap:0.6rem; cursor:pointer;
    padding:0.5rem 0.75rem; border-radius:8px; transition:background 0.2s;
  }
  .radio-option:hover, .checkbox-option:hover { background:rgba(255,255,255,0.05); }
  .radio-option input, .checkbox-option input { accent-color:var(--primary, #3b82f6); width:16px; height:16px; cursor:pointer; }
  .radio-label, .checkbox-label { font-size:0.95rem; }
  .checkbox-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:0.25rem; margin-top:0.4rem; max-height:220px; overflow-y:auto; padding-right:0.5rem; }
  .checkbox-grid::-webkit-scrollbar { width:5px; }
  .checkbox-grid::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:3px; }
  @media (max-width:600px) { .checkbox-grid { grid-template-columns: repeat(2, 1fr); } }

  .cell-actions { display:flex; gap:0.4rem; align-items:center; }
  .icon-action.edit { color:#3b82f6; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.edit:hover { background:rgba(59,130,246,0.12); }
  .icon-action.delete { color:#ef4444; background:none; border:none; cursor:pointer; font-size:1rem; padding:0.3rem; border-radius:6px; }
  .icon-action.delete:hover { background:rgba(239,68,68,0.12); }
</style>

<script>
function openModal(){ document.getElementById('ticketModal').classList.add('active'); }
function closeModal(){ document.getElementById('ticketModal').classList.remove('active'); }

// Tutup modal jika klik di luar area konten
document.getElementById('ticketModal').addEventListener('click', e => { 
  if (e.target === e.currentTarget) closeModal(); 
});

// ===== Mode CREATE: buka modal kosong =====
function openCreateModal(){
  const form = document.getElementById('newTicketForm');
  form.reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Formulir Pencatatan Troubleshoot';
  document.getElementById('submitBtnLabel').textContent = 'Kirim Laporan';
  openModal();
}

// ===== Mode EDIT: buka modal terisi data existing =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('newTicketForm');
  form.reset();

  document.getElementById('field_page_id').value = data.page_id;
  document.getElementById('field_kendala').value = data.kendala;
  document.getElementById('field_deskripsi').value = data.deskripsi || '';
  document.getElementById('field_kesimpulan').value = data.kesimpulan || '';

  // Pilih radio Bidang yang sesuai
  const radio = form.querySelector(`input[name="bidang"][value="${CSS.escape(data.bidang)}"]`);
  if (radio) radio.checked = true;

  // Centang checkbox Nama Petugas yang sesuai
  (data.petugas || []).forEach(nama => {
    const cb = form.querySelector(`input[name="petugas[]"][value="${CSS.escape(nama)}"]`);
    if (cb) cb.checked = true;
  });

  document.getElementById('modalTitle').textContent = 'Edit Laporan Troubleshoot';
  document.getElementById('submitBtnLabel').textContent = 'Simpan Perubahan';
  openModal();
}

// Filter tabel berdasarkan status
function filterTicket(status, el){
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  
  document.querySelectorAll('#ticketTable tbody tr').forEach(row => {
    if (status === 'Semua' || row.dataset.status === status) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Kirim data (create ATAU update, tergantung ada tidaknya page_id)
async function handleNewTicket(event) {
  event.preventDefault();
  
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;
  
  const petugasChecked = [...form.querySelectorAll('input[name="petugas[]"]:checked')].map(cb => cb.value);
  
  if (petugasChecked.length === 0) {
    showToast('⚠️ Pilih minimal satu Nama Petugas!', 'error');
    return;
  }
  
  submitBtn.disabled = true;
  submitBtnLabel.textContent = 'Menyimpan...';
  
  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  const payload = {
    kendala: form.kendala.value.trim(),
    bidang: form.bidang.value,
    deskripsi: form.deskripsi.value.trim(),
    kesimpulan: form.kesimpulan.value.trim(),
    petugas: petugasChecked
  };
  if (isEdit) payload.page_id = pageId;

  const endpoint = isEdit ? '../api/update-troubleshoot.php' : '../api/create-troubleshoot.php';

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
      form.reset();
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (err) {
    console.error('Fetch error:', err);
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtnLabel.textContent = originalLabel;
  }
}

// Hapus (archive) laporan
async function deleteTicket(pageId, title) {
  const confirmed = confirm(`Hapus laporan "${title}"?\n\nLaporan akan dipindahkan ke Trash di Notion.`);
  if (!confirmed) return;

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
    console.error('Fetch error:', err);
    showToast('❌ Gagal menghubungi server: ' + err.message, 'error');
  }
}

// Toast notification helper
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