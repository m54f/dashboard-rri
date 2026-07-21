<?php
require_once __DIR__.'/../api/petugas.php';

$petugas = getPetugas();

if (!is_array($petugas) || isset($petugas['error'])) {
    $errorMessage = $petugas['error'] ?? 'Gagal memuat data';
    $petugas = [];
}

$totalPetugas = count($petugas);
$totalPosisi  = count(array_unique(array_filter(array_column($petugas, 'posisi'), fn($v) => $v !== '-')));
$totalJabatan = count(array_unique(array_filter(array_column($petugas, 'role'), fn($v) => $v !== '-')));

$base = '..'; 
$pageTitle = 'Petugas'; 
$activeMenu = 'petugas';

include __DIR__ . '/../includes/header.php';
?>

<!-- ===== MODERN CSS STYLES ===== -->
<style>
  :root {
    --primary: #4f46e5;
    --primary-hover: #4338ca;
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --radius: 12px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
  }

  body { background-color: var(--bg-page); color: var(--text-main); font-family: 'Inter', system-ui, -apple-system, sans-serif; }

  /* Page Head */
  .page-head {
    display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;
    gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-card);
    border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border);
  }
  .page-head h2 { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main); }
  .page-head p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.95rem; }

  /* Buttons */
  .btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem;
    border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer;
    transition: all 0.2s ease; text-decoration: none;
  }
  .btn-primary { background: var(--primary); color: white; }
  .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
  .btn-secondary { background: #f1f5f9; color: var(--text-main); }
  .btn-secondary:hover { background: #e2e8f0; }

  /* Stats Row */
  .stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;
  }
  .stat-card {
    background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius);
    box-shadow: var(--shadow); border: 1px solid var(--border);
    display: flex; align-items: center; gap: 1.25rem; transition: transform 0.2s;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
  .stat-info h3 { margin: 0; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0.25rem 0; }
  .stat-sub { font-size: 0.8rem; color: var(--text-muted); }
  .stat-icon {
    width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; flex-shrink: 0;
  }
  .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
  .stat-icon.green { background: #f0fdf4; color: #22c55e; }
  .stat-icon.orange { background: #fff7ed; color: #f97316; }

  /* Person Grid */
  .person-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;
  }
  .person-card {
    background: var(--bg-card); border-radius: var(--radius); padding: 1.5rem;
    box-shadow: var(--shadow); border: 1px solid var(--border);
    display: flex; flex-direction: column; align-items: center; text-align: center;
    transition: all 0.25s ease; position: relative;
  }
  .person-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color: #cbd5e1; }
  
  .person-avatar {
    width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #3730a3; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem; border: 3px solid #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
  }
  .person-name { font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem; }
  .person-role { font-size: 0.9rem; color: var(--primary); font-weight: 600; margin-bottom: 1rem; }
  
  .tag-container { display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1.25rem; width: 100%; }
  .tag {
    padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
    background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
  }
  .status-tag.baik { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }

  .person-contact {
    display: flex; gap: 0.75rem; width: 100%; padding-top: 1rem; border-top: 1px solid var(--border);
  }
  .icon-action {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.5rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
    border: 1px solid var(--border); background: #fff; color: var(--text-muted);
    cursor: pointer; transition: all 0.2s; text-decoration: none;
  }
  .icon-action:hover { background: #f8fafc; color: var(--text-main); border-color: #cbd5e1; }
  .icon-action.view:hover { color: #3b82f6; border-color: #3b82f6; background: #eff6ff; }
  .icon-action.edit:hover { color: #f59e0b; border-color: #f59e0b; background: #fffbeb; }
  .icon-action.delete:hover { color: #ef4444; border-color: #ef4444; background: #fef2f2; }

  /* Modal */
  .modal-overlay {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center; z-index: 50;
    opacity: 0; visibility: hidden; transition: all 0.3s ease;
  }
  .modal-overlay.active { opacity: 1; visibility: visible; }
  .modal-content {
    background: var(--bg-card); width: 100%; max-width: 500px; border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    transform: scale(0.95); transition: transform 0.3s ease; overflow: hidden;
  }
  .modal-overlay.active .modal-content { transform: scale(1); }
  .modal-header {
    padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
  }
  .modal-title { margin: 0; font-size: 1.25rem; font-weight: 700; }
  .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
  .modal-close:hover { color: var(--text-main); }
  
  .form-group { margin-bottom: 1.25rem; }
  .form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem; }
  .required-star { color: #ef4444; }
  .form-group input {
    width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;
    font-size: 0.95rem; transition: all 0.2s; box-sizing: border-box; background: #fafafa;
  }
  .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); background: #fff; }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  
  .modal-footer {
    padding: 1.25rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border);
    display: flex; justify-content: flex-end; gap: 0.75rem;
  }

  /* Empty State */
  .empty-state {
    grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; color: var(--text-muted);
    background: var(--bg-card); border-radius: var(--radius); border: 2px dashed var(--border);
  }

  @media (max-width: 640px) {
    .form-row { grid-template-columns: 1fr; }
    .page-head { flex-direction: column; align-items: flex-start; }
    .btn { width: 100%; justify-content: center; }
  }
</style>

<!-- ===== MAIN CONTENT ===== -->
<div class="page-head">
  <div>
    <h2>Manajemen Petugas</h2>
    <p>Daftar tim Divisi Teknologi &amp; Media Baru</p>
  </div>
  <button class="btn btn-primary" onclick="openCreateModal()">
    <i class="ri-add-line"></i> Tambah Petugas
  </button>
</div>

<?php if (isset($errorMessage)): ?>
  <div class="alert alert-danger" style="background:#fef2f2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border: 1px solid #fecaca; display:flex; align-items:center; gap:0.75rem;">
    <i class="ri-error-warning-line" style="font-size:1.25rem;"></i>
    <span>Gagal memuat data dari Notion: <strong><?= htmlspecialchars($errorMessage) ?></strong></span>
  </div>
<?php endif; ?>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="ri-team-line"></i></div>
    <div class="stat-info">
      <h3>Total Petugas</h3>
      <div class="stat-value"><?= $totalPetugas ?></div>
      <div class="stat-sub">Anggota tim TMB</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="ri-briefcase-line"></i></div>
    <div class="stat-info">
      <h3>Total Posisi</h3>
      <div class="stat-value"><?= $totalPosisi ?></div>
      <div class="stat-sub">Penempatan area kerja</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="ri-medal-line"></i></div>
    <div class="stat-info">
      <h3>Total Jabatan</h3>
      <div class="stat-value"><?= $totalJabatan ?></div>
      <div class="stat-sub">Jenjang karir aktif</div>
    </div>
  </div>
</div>

<div class="person-grid">
  <?php if (empty($petugas)): ?>
    <div class="empty-state">
      <i class="ri-user-search-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
      <p style="margin:0; font-size:1.1rem; font-weight:600;">Tidak ada data petugas ditemukan.</p>
      <p style="margin-top:0.5rem; font-size:0.9rem;">Silakan tambahkan data petugas baru atau periksa koneksi Notion API.</p>
    </div>
  <?php else: ?>
    <?php foreach ($petugas as $p): 
      $editPayload = htmlspecialchars(json_encode([
        'page_id' => $p['page_id'],
        'name'    => $p['name'],
        'role'    => $p['role'],
        'posisi'  => $p['posisi'],
        'pangkat' => $p['pangkat'],
        'nip'     => $p['nip']
      ]), ENT_QUOTES, 'UTF-8');
    ?>
    <div class="person-card">
      <div class="person-name"><?= htmlspecialchars($p['name']) ?></div>
      <div class="person-role"><?= htmlspecialchars($p['role']) ?></div>
      
      <div class="tag-container">
        <span class="tag"><i class="ri-map-pin-line" style="margin-right:4px;"></i><?= htmlspecialchars($p['posisi']) ?></span>
        <span class="status-tag baik"><i class="ri-shield-star-line" style="margin-right:4px;"></i><?= htmlspecialchars($p['pangkat']) ?></span>
      </div>
      
      <div class="person-contact">
        <a href="https://www.notion.so/<?= htmlspecialchars($p['page_id']) ?>" target="_blank" class="icon-action view" title="Buka di Notion">
          <i class="ri-external-link-line"></i> Notion
        </a>
        <button class="icon-action edit" title="Edit Data" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
          <i class="ri-pencil-line"></i> Edit
        </button>
        <button class="icon-action delete" title="Hapus Data" onclick="deletePetugas('<?= htmlspecialchars($p['page_id']) ?>', '<?= htmlspecialchars($p['name']) ?>')">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal Form (Create & Edit) -->
<div class="modal-overlay" id="petugasModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">Tambah Petugas</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="petugasForm" onsubmit="handlePetugasSubmit(event)">
      <input type="hidden" id="field_page_id" name="page_id" value="">
      
      <div style="padding: 1.5rem;">
        <div class="form-group">
          <label>Nama Lengkap <span class="required-star">*</span></label>
          <input type="text" id="field_name" name="name" placeholder="Masukkan nama lengkap" required>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>Jabatan <span class="required-star">*</span></label>
            <input type="text" id="field_role" name="role" placeholder="Cth: Asisten Teknisi" required>
          </div>
          <div class="form-group">
            <label>Posisi <span class="required-star">*</span></label>
            <input type="text" id="field_posisi" name="posisi" placeholder="Cth: Studio" required>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group">
            <label>NIP / NIPPK / NRP</label>
            <input type="text" id="field_nip" name="nip" placeholder="Nomor induk pegawai">
          </div>
          <div class="form-group">
            <label>Pangkat / Gol. Ruang</label>
            <input type="text" id="field_pangkat" name="pangkat" placeholder="Cth: III / a">
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="ri-save-line"></i> <span id="submitBtnLabel">Simpan</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(){ document.getElementById('petugasModal').classList.add('active'); }
function closeModal(){ document.getElementById('petugasModal').classList.remove('active'); }

document.getElementById('petugasModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeModal();
});

// ===== MODE CREATE =====
function openCreateModal(){
  const form = document.getElementById('petugasForm');
  form.reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Tambah Petugas';
  document.getElementById('submitBtnLabel').textContent = 'Simpan';
  openModal();
}

// ===== MODE EDIT =====
function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  const form = document.getElementById('petugasForm');
  
  document.getElementById('field_page_id').value = data.page_id;
  document.getElementById('field_name').value = data.name;
  document.getElementById('field_role').value = data.role;
  document.getElementById('field_posisi').value = data.posisi;
  document.getElementById('field_nip').value = data.nip;
  document.getElementById('field_pangkat').value = data.pangkat;
  
  document.getElementById('modalTitle').textContent = 'Edit Petugas';
  document.getElementById('submitBtnLabel').textContent = 'Update';
  openModal();
}

// ===== SUBMIT (CREATE / UPDATE) =====
async function handlePetugasSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const submitBtnLabel = document.getElementById('submitBtnLabel');
  const originalLabel = submitBtnLabel.textContent;

  const pageId = form.page_id.value;
  const isEdit = !!pageId;

  submitBtn.disabled = true;
  submitBtn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> <span>Menyimpan...</span>`;

  const payload = {
    name: form.name.value,
    role: form.role.value,
    posisi: form.posisi.value,
    nip: form.nip.value,
    pangkat: form.pangkat.value
  };
  if (isEdit) payload.page_id = pageId;

  try {
    const endpoint = isEdit ? '../api/update-petugas.php' : '../api/create-petugas.php';
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (data.success) {
      showToast('✅ ' + data.message, 'success');
      closeModal();
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (err) {
    showToast('❌ ' + err.message, 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = `<i class="ri-save-line"></i> <span>${originalLabel}</span>`;
  }
}

// ===== DELETE =====
async function deletePetugas(pageId, name) {
  if (!confirm(`Apakah Anda yakin ingin menghapus data petugas "${name}"?`)) return;
  try {
    const res = await fetch('../api/delete-petugas.php', {
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

// ===== TOAST NOTIFICATION =====
function showToast(message, type) {
  const existing = document.querySelector('.toast-notification');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = 'toast-notification ' + type;
  toast.innerHTML = message;
  toast.style.cssText = `
    position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
    padding: 1rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.95rem;
    color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.18); animation: slideInToast 0.4s ease;
    display: flex; align-items: center; gap: 0.5rem;
    ${type === 'success' ? 'background: linear-gradient(135deg, #22c55e, #16a34a);' : 'background: linear-gradient(135deg, #ef4444, #dc2626);'}
  `;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.5s'; setTimeout(() => toast.remove(), 500); }, 4000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>