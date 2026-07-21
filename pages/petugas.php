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
  .filter-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; }
  .filter-tab { padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); border: 1px solid var(--border); background: #fff; transition: 0.2s; }
  .filter-tab:hover { background: #f1f5f9; }
  .filter-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); }

  :root {
    --primary: #4f46e5; --primary-hover: #4338ca; --bg-page: #f8fafc; --bg-card: #ffffff;
    --text-main: #0f172a; --text-muted: #64748b; --border: #e2e8f0; --radius: 12px;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
  }
  body { background-color: var(--bg-page); color: var(--text-main); font-family: 'Inter', system-ui, sans-serif; }
  .page-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); }
  .page-head h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }
  .page-head p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.95rem; }
  .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; }
  .btn-primary { background: var(--primary); color: white; }
  .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
  .btn-secondary { background: #f1f5f9; color: var(--text-main); }
  .btn-secondary:hover { background: #e2e8f0; }
  
  .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
  .stat-card { background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); display: flex; align-items: center; gap: 1.25rem; transition: transform 0.2s; }
  .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
  .stat-info h3 { margin: 0; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0.25rem 0; }
  .stat-sub { font-size: 0.8rem; color: var(--text-muted); }
  .stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
  .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
  .stat-icon.green { background: #f0fdf4; color: #22c55e; }
  .stat-icon.orange { background: #fff7ed; color: #f97316; }
  .stat-icon.purple { background: #f5f3ff; color: #8b5cf6; }

  .person-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
  .person-card { background: var(--bg-card); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border); display: flex; flex-direction: column; align-items: center; text-align: center; transition: all 0.25s ease; position: relative; }
  .person-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color: #cbd5e1; }
  .person-avatar { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; border: 3px solid #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
  .person-name { font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem; }
  .person-role { font-size: 0.9rem; color: var(--primary); font-weight: 600; margin-bottom: 1rem; }
  .tag-container { display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1.25rem; width: 100%; }
  .tag { padding: 0.3rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
  .status-tag.baik { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
  .person-contact { display: flex; gap: 0.5rem; width: 100%; padding-top: 1rem; border-top: 1px solid var(--border); }
  .icon-action { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.5rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; border: 1px solid var(--border); background: #fff; color: var(--text-muted); cursor: pointer; transition: all 0.2s; text-decoration: none; }
  .icon-action:hover { background: #f8fafc; color: var(--text-main); border-color: #cbd5e1; }
  .icon-action.view:hover { color: #3b82f6; border-color: #3b82f6; background: #eff6ff; }
  .icon-action.edit:hover { color: #f59e0b; border-color: #f59e0b; background: #fffbeb; }
  .icon-action.delete:hover { color: #ef4444; border-color: #ef4444; background: #fef2f2; }
  .icon-action.report:hover { color: #8b5cf6; border-color: #8b5cf6; background: #f5f3ff; }

  /* Report View Styling */
  #reportView { display: none; animation: fadeIn 0.3s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
  .data-table th { text-align: left; padding: 0.75rem 1rem; font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border); background: #f8fafc; }
  .data-table td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
  .mini-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; background: #fff7ed; color: #f97316; }
</style>

<!-- ===== MAIN VIEW (DAFTAR PETUGAS) ===== -->
<div id="mainView">
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
    <div class="alert alert-danger" style="background:#fef2f2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1.5rem; border: 1px solid #fecaca;">
      ⚠️ Gagal memuat data: <?= htmlspecialchars($errorMessage) ?>
    </div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="ri-team-line"></i></div>
      <div class="stat-info"><h3>Total Petugas</h3><div class="stat-value"><?= $totalPetugas ?></div><div class="stat-sub">Anggota tim TMB</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="ri-briefcase-line"></i></div>
      <div class="stat-info"><h3>Total Posisi</h3><div class="stat-value"><?= $totalPosisi ?></div><div class="stat-sub">Penempatan area kerja</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="ri-medal-line"></i></div>
      <div class="stat-info"><h3>Total Jabatan</h3><div class="stat-value"><?= $totalJabatan ?></div><div class="stat-sub">Jenjang karir aktif</div></div>
    </div>
  </div>

  <div class="person-grid">
    <?php if (empty($petugas)): ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 2rem; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius); border: 2px dashed var(--border);">
        <i class="ri-user-search-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
        <p style="margin:0; font-size:1.1rem; font-weight:600;">Tidak ada data petugas ditemukan.</p>
      </div>
    <?php else: ?>
      <?php foreach ($petugas as $p): 
        $editPayload = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
      ?>
      <div class="person-card">
        <div class="person-avatar"><?= htmlspecialchars($p['init']) ?></div>
        <div class="person-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="person-role"><?= htmlspecialchars($p['role']) ?></div>
        
        <div class="tag-container">
          <span class="tag"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($p['posisi']) ?></span>
          <span class="status-tag baik"><i class="ri-shield-star-line"></i> <?= htmlspecialchars($p['pangkat']) ?></span>
        </div>
        
        <div class="person-contact">
          <button class="icon-action report" title="Lihat Laporan Kinerja" onclick="loadLaporan('<?= htmlspecialchars($p['name']) ?>')">
            <i class="ri-file-chart-line"></i> Laporan
          </button>
          <button class="icon-action edit" title="Edit Data" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
            <i class="ri-pencil-line"></i>
          </button>
          <button class="icon-action delete" title="Hapus Data" onclick="deletePetugas('<?= htmlspecialchars($p['page_id']) ?>', '<?= htmlspecialchars($p['name']) ?>')">
            <i class="ri-delete-bin-line"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ===== REPORT VIEW (LAPORAN INDIVIDU) ===== -->
<!-- ===== REPORT VIEW (LAPORAN INDIVIDU) ===== -->
<div id="reportView">
    <div class="page-head">
        <div>
          <h2>Laporan Kinerja: <span id="reportNama" style="color: var(--primary);"></span></h2>
          <p>Rekap aktivitas dari seluruh logbook (Listrik, Troubleshoot, Checklist, Jurnal).</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <button class="btn btn-primary" onclick="exportToExcel()" id="btnExport" style="display: none;">
            <i class="ri-file-excel-line"></i> Export Excel
          </button>
          <button class="btn btn-secondary" onclick="closeReportView()"><i class="ri-arrow-left-line"></i> Kembali</button>
        </div>
    </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="ri-stack-line"></i></div>
      <div class="stat-info"><h3>Total Tugas</h3><div class="stat-value" id="rTotalTugas">0</div><div class="stat-sub">Semua aktivitas</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="ri-time-line"></i></div>
      <div class="stat-info"><h3>Total Jam Kerja</h3><div class="stat-value" id="rTotalDurasi" style="font-size: 1.5rem;">0 Jam</div><div class="stat-sub">Penanganan Pemadaman</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><i class="ri-pie-chart-line"></i></div>
      <div class="stat-info"><h3>Kategori Terbanyak</h3><div class="stat-value" id="rKategori" style="font-size: 1.5rem;">-</div><div class="stat-sub" id="rKategoriDetail">-</div></div>
    </div>
  </div>

  <div style="background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
      <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;"><i class="ri-history-line"></i> Riwayat Aktivitas</h3>
      
            <!-- FILTER TABS (KATEGORI & WAKTU) -->
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
        
        <!-- Filter Kategori Logbook -->
        <div class="filter-tabs" id="reportFilterTabs" style="display: none;">
          <div class="filter-tab active" onclick="filterReport('Semua', this)">Semua Kategori</div>
          <div class="filter-tab" onclick="filterReport('Pemadaman Listrik', this)">Pemadaman</div>
          <div class="filter-tab" onclick="filterReport('Troubleshoot', this)">Troubleshoot</div>
          <div class="filter-tab" onclick="filterReport('Checklist', this)">Checklist</div>
          <div class="filter-tab" onclick="filterReport('Jurnal Kegiatan', this)">Jurnal</div>
        </div>

        <!-- Filter Tahun & Triwulan -->
        <div id="timeFilterWrap" style="display: none; flex-direction: column; gap: 0.5rem;">
          <div style="display:flex; align-items:center; gap:0.5rem;">
            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin:0;">Tahun:</label>
            <select id="filterTahun" onchange="renderReportTable()" style="padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.8rem; font-weight: 600; background: #fff; cursor: pointer;">
              <option value="Semua">Semua Tahun</option>
            </select>
          </div>
          <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterTriwulan('Semua', this)">Semua Waktu</div>
            <div class="filter-tab" onclick="filterTriwulan('1', this)">TW 1</div>
            <div class="filter-tab" onclick="filterTriwulan('2', this)">TW 2</div>
            <div class="filter-tab" onclick="filterTriwulan('3', this)">TW 3</div>
            <div class="filter-tab" onclick="filterTriwulan('4', this)">TW 4</div>
          </div>
        </div>

      </div>

    </div>

    <div style="overflow-x: auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori Logbook</th>
            <th>Deskripsi Aktivitas</th>
            <th>Durasi</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="reportTableBody">
          <tr><td colspan="6" style="text-align:center; padding: 2rem; color: var(--text-muted);">Memuat data laporan...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Form (Create & Edit Petugas) -->
<div class="modal-overlay" id="petugasModal" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 50; opacity: 0; visibility: hidden; transition: all 0.3s ease;">
  <div class="modal-content" style="background: var(--bg-card); width: 100%; max-width: 500px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); transform: scale(0.95); transition: transform 0.3s ease; overflow: hidden;">
    <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
      <h3 class="modal-title" id="modalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700;">Tambah Petugas</h3>
      <button class="modal-close" onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
    </div>
    <form id="petugasForm" onsubmit="handlePetugasSubmit(event)" style="padding: 1.5rem;">
      <input type="hidden" id="field_page_id" name="page_id" value="">
      <div class="form-group" style="margin-bottom: 1.25rem;"><label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Nama Lengkap *</label><input type="text" id="field_name" name="name" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;" required></div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="form-group"><label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Jabatan *</label><input type="text" id="field_role" name="role" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;" required></div>
        <div class="form-group"><label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Posisi *</label><input type="text" id="field_posisi" name="posisi" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;" required></div>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
        <div class="form-group"><label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">NIP</label><input type="text" id="field_nip" name="nip" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;"></div>
        <div class="form-group"><label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Pangkat</label><input type="text" id="field_pangkat" name="pangkat" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px;"></div>
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--border);">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
// Variabel global
let currentReportData = null;
let currentFilter = 'Semua';
let currentTahun = 'Semua';
let currentTriwulan = 'Semua';

// ===== PARSER TANGGAL (Mendukung format ISO & Bahasa Indonesia) =====
function parseTanggalToParts(str) {
  if (!str || str === '-') return null;
  str = String(str).replace(',', '');
  
  // Cek format ISO (2026-07-17)
  let matchIso = str.match(/(\d{4})-(\d{2})-(\d{2})/);
  if (matchIso) return { year: parseInt(matchIso[1]), month: parseInt(matchIso[2]) };
  
  // Cek format Indo (17 Juli 2026 / 17 Jul 2026)
  const months = {'Januari':1,'Februari':2,'Maret':3,'April':4,'Mei':5,'Juni':6,'Juli':7,'Agustus':8,'September':9,'Oktober':10,'November':11,'Desember':12, 'Jan':1,'Feb':2,'Mar':3,'Apr':4,'May':5,'Jun':6,'Jul':7,'Aug':8,'Sep':9,'Oct':10,'Nov':11,'Dec':12};
  let parts = str.split(/[\s]+/);
  if (parts.length >= 3) {
    let monthStr = parts[1];
    let year = parseInt(parts[parts.length-1]);
    let month = months[monthStr] || 0;
    if (year > 2000 && month > 0) return { year: year, month: month };
  }
  return null;
}

// ===== VIEW SWITCHING =====
function loadLaporan(nama) {
  document.getElementById('mainView').style.display = 'none';
  document.getElementById('reportView').style.display = 'block';
  document.getElementById('reportNama').textContent = nama;
  
  const tbody = document.getElementById('reportTableBody');
  tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 2rem;"><i class="ri-loader-4-line" style="animation: spin 1s linear infinite; display:inline-block;"></i> Mengumpulkan data dari seluruh logbook...</td></tr>`;
  
  fetch(`../api/laporan-individu.php?nama=${encodeURIComponent(nama)}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) { alert(data.error); return; }
      
      currentReportData = data;
      currentFilter = 'Semua';
      currentTriwulan = 'Semua';
      currentTahun = 'Semua';
      
      // Tampilkan UI Filter & Tombol Export
      document.getElementById('reportFilterTabs').style.display = 'flex';
      document.getElementById('timeFilterWrap').style.display = 'flex';
      document.getElementById('btnExport').style.display = 'inline-flex';
      
      // Reset tombol filter ke "Semua"
      document.querySelectorAll('#reportFilterTabs .filter-tab').forEach(t => t.classList.remove('active'));
      document.querySelector('#reportFilterTabs .filter-tab').classList.add('active');
      
      document.querySelectorAll('#timeFilterWrap .filter-tab').forEach(t => t.classList.remove('active'));
      document.querySelector('#timeFilterWrap .filter-tab').classList.add('active');
      
      // Isi Dropdown Tahun berdasarkan data yang ada
      let years = new Set();
      data.riwayat.forEach(r => {
        let parts = parseTanggalToParts(r.tanggal);
        if (parts) years.add(parts.year);
      });
      
      let yearSelect = document.getElementById('filterTahun');
      yearSelect.innerHTML = '<option value="Semua">Semua Tahun</option>';
      Array.from(years).sort((a,b) => b-a).forEach(y => {
        yearSelect.innerHTML += `<option value="${y}">${y}</option>`;
      });
      yearSelect.value = 'Semua';
      
      // Update Statistik
      document.getElementById('rTotalTugas').textContent = data.total_tugas;
      document.getElementById('rTotalDurasi').textContent = data.total_durasi;
      
      let maxKat = '-', maxVal = 0;
      for (const [k, v] of Object.entries(data.statistik)) {
        if (v > maxVal) { maxVal = v; maxKat = k; }
      }
      document.getElementById('rKategori').textContent = maxKat;
      document.getElementById('rKategoriDetail').textContent = `Ditangani ${maxVal} kali`;
      
      renderReportTable();
    })
    .catch(err => alert('Gagal memuat laporan: ' + err));
}

function closeReportView() {
  document.getElementById('reportView').style.display = 'none';
  document.getElementById('mainView').style.display = 'block';
  document.getElementById('btnExport').style.display = 'none';
  document.getElementById('reportFilterTabs').style.display = 'none';
  document.getElementById('timeFilterWrap').style.display = 'none';
  currentReportData = null;
}

// ===== FILTER HANDLERS =====
function filterReport(kategori, el) {
  currentFilter = kategori;
  document.querySelectorAll('#reportFilterTabs .filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderReportTable();
}

function filterTriwulan(tw, el) {
  currentTriwulan = tw;
  document.querySelectorAll('#timeFilterWrap .filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderReportTable();
}

// ===== RENDER TABEL (DENGAN FILTER GABUNGAN) =====
function getFilteredData() {
  if (!currentReportData) return [];
  
  return currentReportData.riwayat.filter(r => {
    // Filter Kategori
    let matchKat = (currentFilter === 'Semua' || r.kategori === currentFilter);
    if (!matchKat) return false;
    
    // Filter Tahun & Triwulan
    if (currentTahun === 'Semua' && currentTriwulan === 'Semua') return true;
    
    let parts = parseTanggalToParts(r.tanggal);
    if (!parts) return false;
    
    let matchYear = (currentTahun === 'Semua' || parts.year == currentTahun);
    let matchTriwulan = true;
    
    if (currentTriwulan !== 'Semua') {
      let tw = Math.ceil(parts.month / 3); // 1-3=1, 4-6=2, 7-9=3, 10-12=4
      matchTriwulan = (tw == currentTriwulan);
    }
    
    return matchYear && matchTriwulan;
  });
}

function renderReportTable() {
  const tbody = document.getElementById('reportTableBody');
  if (!currentReportData) return;

  let filteredData = getFilteredData();

  tbody.innerHTML = '';
  if (filteredData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem; color: var(--text-muted);">Tidak ada riwayat aktivitas pada filter ini.</td></tr>';
    return;
  }

  filteredData.forEach(r => {
    let katColor = 'blue';
    if (r.kategori.includes('Troubleshoot')) katColor = 'red';
    if (r.kategori.includes('Checklist')) katColor = 'purple';
    if (r.kategori.includes('Jurnal')) katColor = 'green';
    
    tbody.innerHTML += `
      <tr>
        <td style="font-weight: 600;">${r.tanggal}</td>
        <td><span class="tag" style="background: #f1f5f9; color: #475569; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem;">${r.kategori}</span></td>
        <td>${r.deskripsi}</td>
        <td><span class="mini-badge">${r.durasi}</span></td>
        <td style="font-size: 0.85rem; color: ${r.status.includes('Selesai') || r.status.includes('Lengkap') ? '#16a34a' : '#f97316'};">${r.status}</td>
        <td><a href="${r.url}" target="_blank" class="icon-action view" style="flex: none; width: 32px; height: 32px;" title="Buka di Notion"><i class="ri-external-link-line"></i></a></td>
      </tr>
    `;
  });
}

// ===== MODAL & CRUD =====
function openModal(){ document.getElementById('petugasModal').style.visibility = 'visible'; document.getElementById('petugasModal').style.opacity = '1'; }
function closeModal(){ document.getElementById('petugasModal').style.visibility = 'hidden'; document.getElementById('petugasModal').style.opacity = '0'; }
document.getElementById('petugasModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });

function openCreateModal(){
  document.getElementById('petugasForm').reset();
  document.getElementById('field_page_id').value = '';
  document.getElementById('modalTitle').textContent = 'Tambah Petugas';
  openModal();
}

function openEditModal(btn){
  const data = JSON.parse(btn.dataset.payload);
  document.getElementById('field_page_id').value = data.page_id;
  document.getElementById('field_name').value = data.name;
  document.getElementById('field_role').value = data.role;
  document.getElementById('field_posisi').value = data.posisi;
  document.getElementById('field_nip').value = data.nip;
  document.getElementById('field_pangkat').value = data.pangkat;
  document.getElementById('modalTitle').textContent = 'Edit Petugas';
  openModal();
}

async function handlePetugasSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const pageId = form.page_id.value;
  const isEdit = !!pageId;
  
  const payload = {
    name: form.name.value, role: form.role.value, posisi: form.posisi.value,
    nip: form.nip.value, pangkat: form.pangkat.value
  };
  if (isEdit) payload.page_id = pageId;

  try {
    const res = await fetch(isEdit ? '../api/update-petugas.php' : '../api/create-petugas.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) { alert(data.message); location.reload(); } 
    else { alert('Gagal: ' + (data.error || 'Unknown error')); }
  } catch (err) { alert('Error: ' + err.message); }
}

async function deletePetugas(pageId, name) {
  if (!confirm(`Hapus data petugas "${name}"?`)) return;
  try {
    const res = await fetch('../api/delete-petugas.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ page_id: pageId })
    });
    const data = await res.json();
    if (data.success) { alert(data.message); location.reload(); } 
    else { alert('Gagal: ' + (data.error || 'Unknown error')); }
  } catch (err) { alert('Error: ' + err.message); }
}

// CSS Injection for spin animation
const style = document.createElement('style');
style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
document.head.appendChild(style);

// ===== FUNGSI EXPORT KE EXCEL (DENGAN FILTER GABUNGAN) =====
function exportToExcel() {
    let dataToExport = getFilteredData();
    
    if (dataToExport.length === 0) {
      alert("Tidak ada data pada filter ini untuk diexport!");
      return;
    }

    const nama = currentReportData.nama;
    const tanggalCetak = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    
    // Buat teks deskripsi filter
    let filterDesc = [];
    if (currentFilter !== 'Semua') filterDesc.push(currentFilter);
    if (currentTahun !== 'Semua') filterDesc.push("Tahun " + currentTahun);
    if (currentTriwulan !== 'Semua') filterDesc.push("Triwulan " + currentTriwulan);
    let filterText = filterDesc.length > 0 ? filterDesc.join(' - ') : 'Semua Data';

    let html = `
        <meta charset="UTF-8">
        <h2>Laporan Kinerja Petugas</h2>
        <p><strong>Nama:</strong> ${nama}</p>
        <p><strong>Tanggal Cetak:</strong> ${tanggalCetak}</p>
        <p><strong>Filter Diterapkan:</strong> ${filterText}</p>
        <p><strong>Total Tugas Ditampilkan:</strong> ${dataToExport.length}</p>
        <br>
        <table border="1" cellspacing="0" cellpadding="5" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
            <thead style="background: #4f46e5; color: #ffffff; text-align: center;">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kategori Logbook</th>
                    <th>Deskripsi Aktivitas</th>
                    <th>Durasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;

    dataToExport.forEach((r, index) => {
        html += `
            <tr>
                <td style="text-align: center;">${index + 1}</td>
                <td style="text-align: left;">${r.tanggal}</td>
                <td style="text-align: left;">${r.kategori}</td>
                <td style="text-align: left;">${r.deskripsi}</td>
                <td style="text-align: center;">${r.durasi}</td>
                <td style="text-align: center;">${r.status}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    const uri = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(html);
    const link = document.createElement("a");
    link.href = uri;
    link.style = "visibility:hidden";
    
    // Nama file disesuaikan dengan filter
    let fileName = `Laporan_${nama.replace(/\s+/g, '_')}`;
    if (currentTahun !== 'Semua') fileName += `_${currentTahun}`;
    if (currentTriwulan !== 'Semua') fileName += `_TW${currentTriwulan}`;
    if (currentFilter !== 'Semua') fileName += `_${currentFilter.replace(/\s+/g, '_')}`;
    
    link.download = fileName + ".xls";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Listener untuk dropdown Tahun
document.getElementById('filterTahun').addEventListener('change', function() {
  currentTahun = this.value;
  renderReportTable();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>