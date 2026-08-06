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
$pageTitle = 'Manajemen Petugas'; 
$activeMenu = 'petugas';

include __DIR__ . '/../includes/header.php';
?>

<style>
  :root {
    --primary: #4f46e5; --primary-light: #eef2ff; --primary-hover: #4338ca; 
    --bg-page: #f8fafc; --bg-card: #ffffff; 
    --text-main: #0f172a; --text-muted: #64748b; --text-light: #94a3b8;
    --border: #e2e8f0; --radius: 16px;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-card: 0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05);
    --shadow-hover: 0 20px 25px -5px rgb(79 70 229 / 0.1), 0 8px 10px -6px rgb(79 70 229 / 0.05);
  }

  body { background-color: var(--bg-page); color: var(--text-main); font-family: 'Inter', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; }
  
  /* Page Head */
  .page-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; padding: 1.5rem 2rem; background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
  .page-head h2 { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.03em; color: var(--text-main); }
  .page-head p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.95rem; font-weight: 500; }
  
  /* Buttons */
  .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; }
  .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
  .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 8px 12px -2px rgba(79, 70, 229, 0.3); }
  .btn-secondary { background: #f1f5f9; color: var(--text-main); border: 1px solid var(--border); }
  .btn-secondary:hover { background: #e2e8f0; border-color: #cbd5e1; }

  /* Stats Row */
/* Stats Row - Diperbarui untuk PC & Mobile */
  .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
  .stat-card { background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow-card); border: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-start; gap: 1.25rem; transition: all 0.3s ease; position: relative; overflow: hidden; }
  .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); border-color: #c7d2fe; }
  .stat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; flex-shrink: 0; z-index: 1; }
  .stat-icon.blue { background: #eff6ff; color: #3b82f6; }
  .stat-icon.green { background: #f0fdf4; color: #22c55e; }
  .stat-icon.orange { background: #fff7ed; color: #f97316; }
  .stat-info { z-index: 1; flex: 1; text-align: left !important; }
  .stat-info h3 { margin: 0; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
  .stat-value { font-size: 1.6rem !important; font-weight: 800; color: var(--text-main); margin: 0.3rem 0; line-height: 1.2; letter-spacing: -0.02em; }
  .stat-sub { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; line-height: 1.3; }

  @media (max-width: 640px) {
    .stats-row { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 1.25rem; }
    .stat-card { padding: 0.75rem 0.85rem; gap: 0.5rem; }
    .stat-icon { width: 38px; height: 38px; font-size: 1.1rem; border-radius: 10px; }
    .stat-value { font-size: 1.35rem !important; }
    .stat-sub { font-size: 0.72rem; }
  }
  /* Staff Grid */
  .staff-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
  
  .staff-card { 
    background: var(--bg-card); 
    border-radius: var(--radius); 
    box-shadow: var(--shadow-card); 
    border: 1px solid var(--border); 
    display: flex; 
    flex-direction: column; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    position: relative;
    overflow: hidden;
  }
  
  /* Background Initial for Depth */
  .bg-initial {
    position: absolute;
    right: -15px;
    bottom: -25px;
    font-size: 9rem;
    font-weight: 900;
    color: #f1f5f9;
    pointer-events: none;
    z-index: 0;
    transition: all 0.4s ease;
    line-height: 1;
  }
  
  .staff-card:hover { 
    transform: translateY(-4px); 
    box-shadow: var(--shadow-hover); 
    border-color: #c7d2fe; 
  }
  .staff-card:hover .bg-initial {
    color: #e0e7ff;
    transform: scale(1.05) translate(-5px, 5px);
  }

  .card-body { padding: 1.5rem; position: relative; z-index: 1; flex-grow: 1; }
  
  .staff-role-badge { 
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem; 
    color: var(--primary); 
    font-weight: 700; 
    background: var(--primary-light); 
    padding: 0.25rem 0.75rem; 
    border-radius: 99px; 
    letter-spacing: 0.02em;
    margin-bottom: 0.75rem;
    border: 1px solid rgba(79, 70, 229, 0.15);
    line-height: 1.2;
  }
  
  .staff-name { 
    font-size: 1.35rem; 
    font-weight: 800; 
    color: var(--text-main); 
    margin: 0 0 1rem 0; 
    letter-spacing: -0.025em; 
    line-height: 1.2; 
  }
  
  .detail-row { 
    display: flex; 
    align-items: center; 
    gap: 0.5rem; 
    margin-bottom: 0.5rem; 
    font-size: 0.9rem; 
    color: var(--text-muted); 
    font-weight: 500; 
  }
  .detail-row i { font-size: 1.1rem; color: var(--text-light); }
  .detail-row.highlight { color: var(--text-main); font-weight: 600; }

  .card-footer { 
    padding: 1rem 1.5rem; 
    border-top: 1px solid var(--border); 
    display: flex; 
    justify-content: flex-end; 
    gap: 0.5rem; 
    background: #fafbfc;
    position: relative;
    z-index: 1;
  }
  
  .icon-action { 
    width: 38px; height: 38px; 
    display: flex; align-items: center; justify-content: center; 
    border-radius: 10px; font-size: 1.1rem; border: 1px solid transparent; 
    background: transparent; color: var(--text-muted); cursor: pointer; 
    transition: all 0.2s; 
  }
  .icon-action:hover { background: #fff; border-color: var(--border); color: var(--text-main); transform: translateY(-1px); }
  .icon-action.report:hover { color: #4f46e5; border-color: #c7d2fe; background: #eef2ff; }
  .icon-action.edit:hover { color: #d97706; border-color: #fde68a; background: #fffbeb; }
  .icon-action.delete:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }

  /* Report View */
  #reportView { display: none; animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
  
  .filter-bar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; padding: 1rem 1.25rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); }
  .filter-group { display: flex; gap: 0.4rem; flex-wrap: wrap; }
  .filter-tab { padding: 0.4rem 0.9rem; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); border: 1px solid transparent; background: transparent; transition: 0.2s; }
  .filter-tab:hover { background: #f1f5f9; color: var(--text-main); }
  .filter-tab.active { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2); }
  .filter-tab.cakrabot { background: #f5f3ff; color: #6366f1; border-color: #c4b5fd; }
  .filter-tab.cakrabot:hover { background: #ede9fe; }
  .filter-tab.cakrabot.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border-color: transparent; box-shadow: 0 2px 4px rgba(99,102,241,0.35); }
  
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

  /* Optimasi Responsif Layar Mobile & Tablet */
  @media (max-width: 768px) {
    .page-head {
      flex-direction: column !important;
      align-items: stretch !important;
      gap: 12px !important;
      margin-bottom: 1rem !important;
    }
    .page-head > div:first-child {
      width: 100%;
    }
    .page-head .btn-primary {
      width: 100% !important;
      justify-content: center !important;
    }
    .filter-tabs, .filter-group {
      display: flex !important;
      overflow-x: auto !important;
      white-space: nowrap !important;
      flex-wrap: nowrap !important;
      padding-bottom: 8px !important;
      margin-bottom: 1rem !important;
      -webkit-overflow-scrolling: touch;
      gap: 8px !important;
    }
    .filter-tab {
      flex-shrink: 0 !important;
    }
    .table-wrap {
      overflow-x: auto !important;
      -webkit-overflow-scrolling: touch;
      border-radius: 0 0 12px 12px;
    }
    .data-table {
      min-width: 700px !important;
    }
    .data-table th, .data-table td {
      padding: 0.75rem 0.6rem !important;
      font-size: 0.83rem !important;
    }
    .pagination-wrap {
      flex-direction: column;
      align-items: stretch !important;
      text-align: center;
    }
  }

  @media (max-width: 640px) {
    .stats-row {
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 10px !important;
    }
    .stats-row > *:last-child:nth-child(odd) {
      grid-column: span 2;
    }
  }

  .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
  .data-table th { text-align: left; padding: 0.85rem 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; border-bottom: 2px solid var(--border); background: #f8fafc; }
  .data-table th:first-child { border-top-left-radius: 8px; }
  .data-table th:last-child { border-top-right-radius: 8px; }
  .data-table td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; background: var(--bg-card); color: var(--text-main); transition: background 0.15s; }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table tbody tr:hover td { background: #f8fafc; }

  /* Modal */
  .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; z-index: 50; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
  .modal-overlay.active { opacity: 1; visibility: visible; }
  .modal-content { background: var(--bg-card); width: 100%; max-width: 520px; border-radius: var(--radius); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: scale(0.95) translateY(10px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; border: 1px solid var(--border); }
  .modal-overlay.active .modal-content { transform: scale(1) translateY(0); }
  .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
  .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
  .modal-close:hover { background: #e2e8f0; color: var(--text-main); }
  
  .form-group { margin-bottom: 1.15rem; }
  .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: var(--text-main); }
  .form-group input, .form-group select { width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--border); border-radius: 10px; font-size: 0.9rem; transition: all 0.2s; outline: none; box-sizing: border-box; background: #fff; }
  .form-group input:focus, .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

  /* Input Search Style */
  .search-input {
    padding: 0.5rem 0.75rem 0.5rem 2rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    font-size: 0.85rem;
    outline: none;
    width: 200px;
    transition: all 0.2s;
    background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="%2364748b"><path d="M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 16.032 2 11C2 5.968 6.032 2 11 2C15.968 2 20 5.968 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z"></path></svg>') no-repeat 10px center;
  }
  .search-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    width: 250px;
  }
</style>

<!-- ===== MAIN VIEW ===== -->
<div id="mainView">
  <div class="page-head">
    <div>
      <h2>Manajemen Petugas</h2>
      <p>Daftar tim Divisi Teknologi &amp; Media Baru</p>
    </div>
    
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <button class="btn btn-secondary" onclick="openGlobalReport()">
        <i class="ri-file-search-line"></i> Laporan Keseluruhan
      </button>
      
      <?php if (getRole() === 'admin'): ?>
      <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="ri-add-line"></i> Tambah Petugas
      </button>
      <?php endif; ?>
    </div>
  </div>

  <?php if (isset($errorMessage)): ?>
    <div style="background:#fef2f2; color:#991b1b; padding:1rem 1.25rem; border-radius:12px; margin-bottom:1.5rem; border: 1px solid #fecaca; display:flex; align-items:center; gap:0.75rem; font-weight: 500;">
      <i class="ri-error-warning-line" style="font-size:1.25rem;"></i>
      <span>Gagal memuat data: <?= htmlspecialchars($errorMessage) ?></span>
    </div>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="ri-team-line"></i></div>
      <div class="stat-info">
        <h3>Total Petugas</h3>
        <div class="stat-value"><?= $totalPetugas ?></div>
        <div class="stat-sub">Anggota tim TMB aktif</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="ri-briefcase-line"></i></div>
      <div class="stat-info">
        <h3>Total Posisi</h3>
        <div class="stat-value"><?= $totalPosisi ?></div>
        <div class="stat-sub">Area kerja yang tercover</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="ri-medal-line"></i></div>
      <div class="stat-info">
        <h3>Total Jabatan</h3>
        <div class="stat-value"><?= $totalJabatan ?></div>
        <div class="stat-sub">Jenjang karir berbeda</div>
      </div>
    </div>
  </div>

  <div class="staff-grid">
    <?php if (empty($petugas)): ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 5rem 2rem; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius); border: 2px dashed var(--border);">
        <i class="ri-user-search-line" style="font-size: 3.5rem; margin-bottom: 1rem; opacity: 0.3; color: var(--primary);"></i>
        <p style="margin:0; font-size:1.15rem; font-weight:600; color: var(--text-main);">Belum ada data petugas</p>
        <p style="margin-top:0.5rem; font-size:0.9rem;">Mulai dengan menambahkan anggota tim baru.</p>
      </div>
    <?php else: ?>
      <?php foreach ($petugas as $p): 
        $editPayload = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
        $initial = htmlspecialchars(strtoupper(substr($p['name'], 0, 1)));
      ?>
      <div class="staff-card">
        <div class="bg-initial"><?= $initial ?></div>
        
        <div class="card-body">
          <span class="staff-role-badge"><?= htmlspecialchars($p['role']) ?></span>
          <h3 class="staff-name"><?= htmlspecialchars($p['name']) ?></h3>
          
          <div class="detail-row">
            <i class="ri-map-pin-2-line"></i>
            <span><?= htmlspecialchars($p['posisi']) ?></span>
          </div>
          
          <?php if (!empty($p['pangkat']) && $p['pangkat'] !== '-'): ?>
            <div class="detail-row highlight">
              <i class="ri-shield-star-line" style="color: var(--primary);"></i>
              <span><?= htmlspecialchars($p['pangkat']) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-footer">
          <button class="icon-action report" title="Lihat Laporan Kinerja" onclick="loadLaporan('<?= htmlspecialchars($p['name']) ?>')">
            <i class="ri-file-chart-line"></i>
          </button>
          <?php if (getRole() === 'admin'): ?>
          <button class="icon-action edit" title="Edit Data" data-payload='<?= $editPayload ?>' onclick="openEditModal(this)">
            <i class="ri-pencil-line"></i>
          </button>
          <button class="icon-action delete" title="Hapus Data" onclick="deletePetugas('<?= htmlspecialchars($p['page_id']) ?>', '<?= htmlspecialchars($p['name']) ?>')">
            <i class="ri-delete-bin-line"></i>
          </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ===== REPORT VIEW ===== -->
<div id="reportView">
  <div class="page-head">
    <div>
      <h2 style="display:flex; align-items:center; gap:0.75rem; margin:0;">
        <button class="btn btn-secondary" onclick="closeReportView()" style="padding:0.4rem 0.6rem;"><i class="ri-arrow-left-line"></i></button>
        <span>Laporan Kinerja: <span id="reportNama" style="color: var(--primary);"></span></span>
      </h2>
      <p style="margin-top:0.25rem;">Rekap aktivitas dari seluruh logbook (Listrik, Troubleshoot, Checklist, Jurnal, Youtube, Peminjaman).</p>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
      <div id="exportFotoWrap" style="display: none; align-items: center;">
        <label style="display:inline-flex; align-items:center; gap:0.4rem; font-size:0.85rem; color:var(--text-muted); cursor:pointer;">
            <input type="checkbox" id="exportFoto" style="cursor:pointer;">
            <span>Sertakan Foto</span>
        </label>
      </div>
      <button class="btn btn-primary" onclick="exportToExcel()" id="btnExport" style="display: none;">
        <i class="ri-file-excel-line"></i> Export Excel
      </button>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="ri-stack-line"></i></div>
      <div class="stat-info"><h3>Total Tugas</h3><div class="stat-value" id="rTotalTugas">0</div><div class="stat-sub">Semua aktivitas</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><i class="ri-time-line"></i></div>
      <div class="stat-info"><h3>Total Jam Kerja</h3><div class="stat-value" id="rTotalDurasi" style="font-size: 1.75rem;">0 Jam</div><div class="stat-sub">Penanganan Pemadaman</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="ri-pie-chart-2-line"></i></div>
      <div class="stat-info"><h3>Kategori Terbanyak</h3><div class="stat-value" id="rKategori" style="font-size: 1.5rem;">-</div><div class="stat-sub" id="rKategoriDetail">-</div></div>
    </div>
  </div>

  <div style="background: var(--bg-card); border-radius: var(--radius); box-shadow: var(--shadow-card); border: 1px solid var(--border); padding: 1.5rem;">
    <div class="filter-bar" id="reportFilterBar" style="display: none;">
      
      <div style="display:flex; gap:1rem; align-items:center; flex-wrap: wrap;">
        <!-- Dropdown Cari Pegawai -->
        <select id="filterPegawai" onchange="loadLaporan(this.value)" style="padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--primary); font-size: 0.85rem; font-weight: 700; background: var(--primary-light); color: var(--primary); cursor: pointer; outline: none;">
          <option value="">Semua Pegawai</option>
          <?php
          $sortedPetugas = $petugas;
          usort($sortedPetugas, fn($a, $b) => strcmp($a['name'], $b['name']));
          foreach ($sortedPetugas as $p): ?>
            <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Search Bar Teks -->
        <input type="text" id="reportSearch" class="search-input" placeholder="Cari aktivitas..." oninput="handleReportSearch(this.value)">
        
        <div style="width: 1px; height: 24px; background: var(--border);"></div>

        <div class="filter-group" id="reportFilterTabs">
          <div class="filter-tab active" onclick="filterReport('Semua', this)">Semua</div>
          <div class="filter-tab" onclick="filterReport('Pemadaman Listrik', this)">Pemadaman</div>
          <div class="filter-tab" onclick="filterReport('Troubleshoot', this)">Troubleshoot</div>
          <div class="filter-tab" onclick="filterReport('Checklist', this)">Checklist</div>
          <div class="filter-tab" onclick="filterReport('Jurnal Kegiatan', this)">Jurnal</div>
          <div class="filter-tab" onclick="filterReport('Rekap Youtube', this)">Youtube</div>
          <div class="filter-tab" onclick="filterReport('Peminjaman Alat', this)">Peminjaman</div>
          <div class="filter-tab cakrabot" onclick="filterReport('Tiket CakraBot', this)" id="tabCakrabot">🤖 CakraBot</div>
        </div>
      </div>
      
      <div style="display:flex; gap:0.75rem; align-items:center; border-left: 1px solid var(--border); padding-left: 1rem;">
        <select id="filterTahun" onchange="filterTahunChanged(this)" style="padding: 0.4rem 0.6rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.85rem; font-weight: 600; background: #fff; cursor: pointer; outline: none;">
          <option value="Semua">Semua Tahun</option>
        </select>
        <div class="filter-group" id="timeFilterWrap">
          <div class="filter-tab active" onclick="filterTriwulan('Semua', this)">Semua TW</div>
          <div class="filter-tab" onclick="filterTriwulan('1', this)">TW 1</div>
          <div class="filter-tab" onclick="filterTriwulan('2', this)">TW 2</div>
          <div class="filter-tab" onclick="filterTriwulan('3', this)">TW 3</div>
          <div class="filter-tab" onclick="filterTriwulan('4', this)">TW 4</div>
        </div>
      </div>
    </div>

    <!-- Tabel Data -->
    <div style="overflow-x: auto; border-radius: 12px 12px 0 0; border: 1px solid var(--border); border-bottom: none;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Deskripsi Aktivitas</th>
            <th style="text-align:center;">Foto</th>
            <th>Durasi</th>
            <th>Status</th>
            <th style="text-align:center;">Aksi</th>
          </tr>
        </thead>
        <tbody id="reportTableBody">
          <tr><td colspan="7" style="text-align:center; padding: 3rem; color: var(--text-muted);">
            <i class="ri-loader-4-line" style="animation: spin 1s linear infinite; display:inline-block; font-size:1.5rem;"></i><br>
            <span style="margin-top:0.5rem; display:block; font-weight:500;">Memuat data laporan...</span>
          </td></tr>
        </tbody>
      </table>
    </div>
    
    <!-- Paging Container -->
    <div class="pagination-wrap" id="reportPaginationWrap" style="display: none; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--border); background: #fff; border-radius: 0 0 12px 12px;">
      <div class="pagination-info" id="reportPaginationInfo" style="font-size: 0.85rem; color: #64748b;">Menampilkan data</div>
      <div class="pagination-buttons" id="reportPaginationButtons" style="display: flex; gap: 8px; align-items: center;"></div>
    </div>

  </div>
</div>

<!-- Modal Overlay Loader Ekstraksi Laporan (Figma Loader Spec) -->
<div class="modal-overlay" id="exportLoaderOverlay" style="z-index: 10000;">
  <div class="modal-content" style="max-width: 360px; text-align: center; padding: 2.25rem 1.75rem; border-radius: 16px; background: #ffffff; box-shadow: 0 20px 40px rgba(0,0,0,0.18);">
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.25rem;">
      
      <!-- Container Loader 40px Presisi Spesifikasi Figma -->
      <div class="figtree-loader-box">
        <div class="figtree-loader-circle"></div>
      </div>
      
      <!-- Display Angka Real Persentase & Status Ekstraksi -->
      <div>
        <div id="exportLoaderPct" style="font-family: 'Figtree', sans-serif; font-size: 32px; font-weight: 800; color: #323338; line-height: 1.2; letter-spacing: -0.5px;">0%</div>
        <div id="exportLoaderStatus" style="font-family: 'Figtree', sans-serif; font-size: 14px; font-weight: 500; color: #64748b; margin-top: 6px;">Mengekstrak data...</div>
      </div>
      
      <!-- Progress Bar Visual -->
      <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-top: 0.25rem;">
        <div id="exportLoaderBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #323338, #0073EA); border-radius: 99px; transition: width 0.2s ease;"></div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="petugasModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700;">Tambah Petugas</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <form id="petugasForm" onsubmit="handlePetugasSubmit(event)" style="padding: 1.5rem;">
      <input type="hidden" id="field_page_id" name="page_id" value="">
      
      <div class="form-group">
        <label>Nama Lengkap <span style="color:#ef4444">*</span></label>
        <input type="text" id="field_name" name="name" required placeholder="Contoh: Budi Santoso">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Jabatan <span style="color:#ef4444">*</span></label>
          <input type="text" id="field_role" name="role" required placeholder="Contoh: Teknisi">
        </div>
        <div class="form-group">
          <label>Posisi <span style="color:#ef4444">*</span></label>
          <input type="text" id="field_posisi" name="posisi" required placeholder="Contoh: Studio 1">
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>NIP <span style="font-weight:400; color:var(--text-muted); font-size:0.8rem;">(Opsional)</span></label>
          <input type="text" id="field_nip" name="nip" placeholder="1990xxxx">
        </div>
        <div class="form-group">
          <label>Pangkat <span style="font-weight:400; color:var(--text-muted); font-size:0.8rem;">(Opsional)</span></label>
          <input type="text" id="field_pangkat" name="pangkat" placeholder="Contoh: Penata Muda">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border); margin-top: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Data</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script>
// ===== State Variables =====
let currentReportData = null;
let currentFilter = 'Semua';
let currentTahun = 'Semua';
let currentTriwulan = 'Semua';
let currentSearchQuery = '';

// Paging Variables
let reportCurrentPage = 1;
const reportRowsPerPage = 10;

function parseTanggalToParts(str) {
  if (!str || str === '-') return null;
  str = String(str).replace(',', '');
  let matchIso = str.match(/(\d{4})-(\d{2})-(\d{2})/);
  if (matchIso) return { year: parseInt(matchIso[1]), month: parseInt(matchIso[2]) };
  
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

// Fungsi membuka laporan global
function openGlobalReport() {
  document.getElementById('filterPegawai').value = '';
  document.getElementById('reportSearch').value = ''; 
  loadLaporan(''); 
}

function loadLaporan(nama) {
  document.getElementById('mainView').style.display = 'none';
  document.getElementById('reportView').style.display = 'block';
  document.getElementById('reportNama').textContent = nama ? nama : 'Semua Pegawai';
  document.getElementById('filterPegawai').value = nama; 
  
  const tbody = document.getElementById('reportTableBody');
  const paginationWrap = document.getElementById('reportPaginationWrap');
  if(paginationWrap) paginationWrap.style.display = 'none';

  tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 3rem; color: var(--text-muted);"><i class="ri-loader-4-line" style="animation: spin 1s linear infinite; display:inline-block; font-size:1.5rem;"></i><br><span style="margin-top:0.5rem; display:block; font-weight:500;">Mengumpulkan data dari seluruh logbook...</span></td></tr>`;
  
  fetch(`../api/laporan-individu.php?nama=${encodeURIComponent(nama)}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) { showToast('❌ ' + data.error, 'error'); closeReportView(); return; }
      
      currentReportData = data;
      
      // Reset State
      currentFilter = 'Semua'; 
      currentTriwulan = 'Semua'; 
      currentTahun = 'Semua';
      currentSearchQuery = document.getElementById('reportSearch').value; 
      reportCurrentPage = 1;
      
      document.getElementById('reportFilterBar').style.display = 'flex';
      document.getElementById('btnExport').style.display = 'inline-flex';
      if (document.getElementById('exportFotoWrap')) document.getElementById('exportFotoWrap').style.display = 'inline-flex';
      
      document.querySelectorAll('#reportFilterTabs .filter-tab, #timeFilterWrap .filter-tab').forEach(t => t.classList.remove('active'));
      document.querySelector('#reportFilterTabs .filter-tab').classList.add('active');
      document.querySelector('#timeFilterWrap .filter-tab').classList.add('active');
      
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
      
      document.getElementById('rTotalTugas').textContent = data.total_tugas;
      document.getElementById('rTotalDurasi').textContent = data.total_durasi;
      
      let maxKat = '-', maxVal = 0;
      for (const [k, v] of Object.entries(data.statistik)) {
        if (v > maxVal) { maxVal = v; maxKat = k; }
      }
      document.getElementById('rKategori').textContent = maxKat;
      document.getElementById('rKategoriDetail').textContent = `Ditangani ${maxVal} kali`;
      
      renderReportTable();

      // Prefetch gambar di background (tidak blocking UI)
      const fotoUrls = [...new Set(data.riwayat.filter(r => r.foto).map(r => r.foto))];
      if (fotoUrls.length > 0) {
          setTimeout(() => {
              parallelFetch(fotoUrls, 6).catch(() => {}); // ignore error
          }, 1000); // tunggu 1 detik setelah tabel render
      }
    })
    .catch(err => { showToast('❌ Gagal memuat laporan: ' + err, 'error'); closeReportView(); });
}

function closeReportView() {
  document.getElementById('reportView').style.display = 'none';
  document.getElementById('mainView').style.display = 'block';
  document.getElementById('btnExport').style.display = 'none';
  if (document.getElementById('exportFotoWrap')) document.getElementById('exportFotoWrap').style.display = 'none';
  document.getElementById('reportFilterBar').style.display = 'none';
  document.getElementById('reportPaginationWrap').style.display = 'none';
  document.getElementById('reportSearch').value = ''; 
  currentReportData = null;
}

// ===== EVENT HANDLERS =====
function filterReport(kategori, el) {
  currentFilter = kategori;
  reportCurrentPage = 1;
  document.querySelectorAll('#reportFilterTabs .filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderReportTable();
}

function filterTriwulan(tw, el) {
  currentTriwulan = tw;
  reportCurrentPage = 1;
  document.querySelectorAll('#timeFilterWrap .filter-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  renderReportTable();
}

function filterTahunChanged(el) {
  currentTahun = el.value;
  reportCurrentPage = 1;
  renderReportTable();
}

function handleReportSearch(val) {
  currentSearchQuery = val;
  reportCurrentPage = 1;
  renderReportTable();
}

function changeReportPage(page) {
  reportCurrentPage = page;
  renderReportTable();
}

function jumpToReportPage(val, maxPage) {
  let p = parseInt(val, 10);
  if (!isNaN(p) && p >= 1 && p <= maxPage) {
    changeReportPage(p);
  } else if (p > maxPage) {
    changeReportPage(maxPage);
  } else {
    changeReportPage(1); 
  }
}

function getFilteredData() {
  if (!currentReportData) return [];
  return currentReportData.riwayat.filter(r => {
    // 1. Kategori Filter
    let matchKat = (currentFilter === 'Semua' || r.kategori === currentFilter);
    if (!matchKat) return false;
    
    // 2. Tahun & Triwulan Filter
    let matchTime = false;
    if (currentTahun === 'Semua' && currentTriwulan === 'Semua') {
      matchTime = true;
    } else {
      let parts = parseTanggalToParts(r.tanggal);
      if (parts) {
        let matchYear = (currentTahun === 'Semua' || parts.year == currentTahun);
        let matchTriwulan = true;
        if (currentTriwulan !== 'Semua') {
          let tw = Math.ceil(parts.month / 3);
          matchTriwulan = (tw == currentTriwulan);
        }
        matchTime = matchYear && matchTriwulan;
      }
    }
    if (!matchTime) return false;

    // 3. Search Bar Filter
    if (currentSearchQuery) {
      const query = currentSearchQuery.toLowerCase();
      const desc = (r.deskripsi || '').toLowerCase();
      const kat = (r.kategori || '').toLowerCase();
      const tgl = (r.tanggal || '').toLowerCase();
      
      if (!desc.includes(query) && !kat.includes(query) && !tgl.includes(query)) {
        return false;
      }
    }
    
    return true;
  });
}

function renderReportTable() {
  const tbody = document.getElementById('reportTableBody');
  const paginationWrap = document.getElementById('reportPaginationWrap');
  const paginationInfo = document.getElementById('reportPaginationInfo');
  const paginationButtons = document.getElementById('reportPaginationButtons');

  if (!currentReportData) return;

  let filteredData = getFilteredData();
  
  if (filteredData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 3rem; color: var(--text-muted);">Tidak ada riwayat aktivitas pada pencarian/filter ini.</td></tr>';
    if(paginationWrap) paginationWrap.style.display = 'none';
    return;
  }

  // Hitung Paging
  const totalRows = filteredData.length;
  const totalPages = Math.ceil(totalRows / reportRowsPerPage) || 1;

  if (reportCurrentPage > totalPages) reportCurrentPage = totalPages;
  if (reportCurrentPage < 1) reportCurrentPage = 1;

  const startIndex = (reportCurrentPage - 1) * reportRowsPerPage;
  const endIndex = Math.min(startIndex + reportRowsPerPage, totalRows);
  
  // Potong Array
  const pagedData = filteredData.slice(startIndex, endIndex);

  // Render Tabel
  tbody.innerHTML = '';
  pagedData.forEach(r => {
    let statusColor = r.status.includes('Selesai') || r.status.includes('Lengkap') ? '#16a34a' : '#f97316';
    let proxyFotoUrl = r.foto ? '../api/image-proxy.php?url=' + encodeURIComponent(r.foto) + '&w=150' : '';
    let fotoHtml = r.foto ? `<img src="${proxyFotoUrl}" loading="lazy" alt="Foto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; cursor: pointer;" onclick="openLightbox('${r.foto}')">` : `<span style="color:#94a3b8;">-</span>`;
    
    tbody.innerHTML += `
      <tr>
        <td style="font-weight: 600; white-space: nowrap;">${r.tanggal}</td>
        <td><span style="padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #475569;">${r.kategori}</span></td>
        <td style="max-width: 300px; line-height: 1.4;">${r.deskripsi}</td>
        <td style="text-align: center;">${fotoHtml}</td>
        <td style="white-space: nowrap; font-weight: 600; color: var(--primary);">${r.durasi}</td>
        <td style="font-size: 0.85rem; color: ${statusColor}; font-weight: 600;">${r.status}</td>
        <td style="text-align:center;"><a href="${r.url}" target="_blank" class="icon-action report" style="display:inline-flex; width:32px; height:32px;" title="Buka di Notion"><i class="ri-external-link-line"></i></a></td>
      </tr>
    `;
  });

  // Render UI Paging
  paginationWrap.style.display = 'flex';
  paginationWrap.style.alignItems = 'center';
  paginationWrap.style.justifyContent = 'space-between';
  paginationInfo.textContent = `Menampilkan ${startIndex + 1}-${endIndex} dari ${totalRows} laporan`;

  paginationButtons.style.display = 'flex';
  paginationButtons.style.alignItems = 'center';
  paginationButtons.style.gap = '0.5rem';

  let buttonsHTML = '';

  const prevDisabled = reportCurrentPage === 1 ? 'disabled' : '';
  const prevStyle = reportCurrentPage === 1 ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
  buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${prevStyle}" onclick="changeReportPage(${reportCurrentPage - 1})" ${prevDisabled}>&laquo; Prev</button>`;

  buttonsHTML += `<input type="text" inputmode="numeric" pattern="[0-9]*" class="page-input-clean" value="${reportCurrentPage}" style="width:50px; padding:0.4rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem; text-align:center; color:#2563eb; outline:none; font-weight:600;" onfocus="this.select()" onchange="jumpToReportPage(this.value, ${totalPages})" onkeydown="if(event.key === 'Enter') jumpToReportPage(this.value, ${totalPages})">`;

  buttonsHTML += `<span style="font-size:0.9rem; color:#475569; display:inline-flex; align-items:center;">dari ${totalPages}</span>`;

  const nextDisabled = reportCurrentPage === totalPages ? 'disabled' : '';
  const nextStyle = reportCurrentPage === totalPages ? 'color:#9ca3af; cursor:not-allowed;' : 'color:#475569; cursor:pointer;';
  buttonsHTML += `<button type="button" style="padding:0.4rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:0.9rem; transition:0.2s; ${nextStyle}" onclick="changeReportPage(${reportCurrentPage + 1})" ${nextDisabled}>Next &raquo;</button>`;

  paginationButtons.innerHTML = buttonsHTML;
}

function openModal(){ document.getElementById('petugasModal').classList.add('active'); }
function closeModal(){ document.getElementById('petugasModal').classList.remove('active'); }
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
  document.getElementById('field_nip').value = data.nip || '';
  document.getElementById('field_pangkat').value = data.pangkat || '';
  document.getElementById('modalTitle').textContent = 'Edit Data Petugas';
  openModal();
}

async function handlePetugasSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const pageId = form.page_id.value;
  const isEdit = !!pageId;
  const submitBtn = document.getElementById('submitBtn');
  
  const payload = { name: form.name.value, role: form.role.value, posisi: form.posisi.value, nip: form.nip.value, pangkat: form.pangkat.value };
  if (isEdit) payload.page_id = pageId;

  submitBtn.disabled = true; submitBtn.textContent = 'Menyimpan...';

  try {
    const res = await fetch(isEdit ? '../api/update-petugas.php' : '../api/create-petugas.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) { 
      showToast('✅ Data berhasil disimpan!', 'success');
      setTimeout(() => location.reload(), 1000);
    } else { 
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error'); 
    }
  } catch (err) { 
    showToast('❌ Error: ' + err.message, 'error'); 
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan Data';
  }
}

async function deletePetugas(pageId, name) {
  if (!confirm(`Hapus data petugas "${name}"?\n\nData akan dipindahkan ke Trash di Notion.`)) return;
  try {
    const res = await fetch('../api/delete-petugas.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ page_id: pageId })
    });
    const data = await res.json();
    if (data.success) { 
      showToast('🗑️ Data berhasil dihapus!', 'success');
      setTimeout(() => location.reload(), 1000);
    } else { 
      showToast('❌ Gagal: ' + (data.error || 'Unknown error'), 'error'); 
    }
  } catch (err) { 
    showToast('❌ Error: ' + err.message, 'error'); 
  }
}

async function parallelFetch(urls, concurrency = 10, onProgress = null) {
    const results = new Array(urls.length).fill(null);
    const queue = urls.map((url, idx) => ({ url, idx }));
    let completed = 0;
    
    const workers = Array.from({ length: Math.min(concurrency, queue.length) }, async () => {
        while (queue.length) {
            const { url, idx } = queue.shift();
            try {
                const res = await fetch('../api/image-proxy.php?url=' + encodeURIComponent(url));
                if (res.ok) {
                    const blob = await res.blob();
                    const buffer = await blob.arrayBuffer();
                    results[idx] = { buffer, extension: blob.type.includes('png') ? 'png' : 'jpeg' };
                }
            } catch (e) {
                console.warn('Gagal fetch gambar:', url);
            } finally {
                completed++;
                if (typeof onProgress === 'function') {
                    onProgress(completed, urls.length);
                }
            }
        }
    });
    
    await Promise.all(workers);
    return results;
}

function showExportLoader() {
  const overlay = document.getElementById('exportLoaderOverlay');
  if (overlay) overlay.classList.add('active');
  updateExportLoaderProgress(0, 'Memulai ekstraksi data...');
}

function updateExportLoaderProgress(pct, statusText) {
  const pctEl = document.getElementById('exportLoaderPct');
  const statusEl = document.getElementById('exportLoaderStatus');
  const barEl = document.getElementById('exportLoaderBar');
  
  const cleanPct = Math.min(100, Math.max(0, Math.round(pct)));
  if (pctEl) pctEl.textContent = cleanPct + '%';
  if (statusEl && statusText) statusEl.textContent = statusText;
  if (barEl) barEl.style.width = cleanPct + '%';
}

function hideExportLoader() {
  const overlay = document.getElementById('exportLoaderOverlay');
  if (overlay) overlay.classList.remove('active');
}

async function exportToExcel() {
  let dataToExport = getFilteredData();
  if (dataToExport.length === 0) { showToast("⚠️ Tidak ada data pada filter ini!", "error"); return; }

  const btnExport = document.getElementById('btnExport');
  const originalText = btnExport.innerHTML;
  btnExport.disabled = true;
  showExportLoader();

  try {
    const includeFoto = document.getElementById('exportFoto')?.checked ?? false;
    const photoBuffers = new Array(dataToExport.length).fill(null);

    if (includeFoto) {
        updateExportLoaderProgress(0, 'Menyiapkan unduhan foto...');
        btnExport.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> 0%...';
        
        const indexWithPhoto = [];
        dataToExport.forEach((r, idx) => { if (r.foto) indexWithPhoto.push(idx); });
        
        if (indexWithPhoto.length > 0) {
            const fotoUrls = indexWithPhoto.map(idx => dataToExport[idx].foto);
            const fetchedPhotos = await parallelFetch(fotoUrls, 10, (done, total) => {
                const progress = Math.round((done / total) * 85);
                updateExportLoaderProgress(progress, `Mengunduh foto (${done}/${total})...`);
                btnExport.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> ${progress}%...`;
            }); // 10 concurrent
            
            fetchedPhotos.forEach((img, i) => {
                if (img) photoBuffers[indexWithPhoto[i]] = img;
            });
        }
        
        updateExportLoaderProgress(90, 'Menyusun file Excel...');
        btnExport.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyusun Excel...';
    } else {
        updateExportLoaderProgress(50, 'Menyusun file Excel...');
        btnExport.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Menyusun Excel...';
    }

    const nama = currentReportData.nama ? currentReportData.nama : 'Semua Pegawai';
    const tanggalCetak = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    let filterDesc = [];
    if (currentFilter !== 'Semua') filterDesc.push(currentFilter);
    if (currentTahun !== 'Semua') filterDesc.push("Tahun " + currentTahun);
    if (currentTriwulan !== 'Semua') filterDesc.push("TW " + currentTriwulan);
    if (currentSearchQuery) filterDesc.push("Cari: " + currentSearchQuery);
    let filterText = filterDesc.length > 0 ? filterDesc.join(' - ') : 'Semua Data';

    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'Logbook RRI';
    const sheet = workbook.addWorksheet('Laporan');

    sheet.columns = [
      { width: 5 },   
      { width: 14 },  
      { width: 16 },  
      { width: 45 },  
      { width: 13 },  
      { width: 10 },  
      { width: 14 },  
    ];

    sheet.mergeCells('A1:G1');
    sheet.getCell('A1').value = 'Laporan Kinerja Petugas';
    sheet.getCell('A1').font = { size: 14, bold: true };

    sheet.mergeCells('A2:G2');
    sheet.getCell('A2').value = `Nama: ${nama}   |   Tanggal: ${tanggalCetak}`;
    sheet.getCell('A2').font = { size: 10, color: { argb: 'FF64748B' } };

    sheet.mergeCells('A3:G3');
    sheet.getCell('A3').value = `Filter: ${filterText}   |   Total: ${dataToExport.length} tugas`;
    sheet.getCell('A3').font = { size: 10, color: { argb: 'FF64748B' } };

    sheet.addRow([]);

    const headerRow = sheet.addRow(['No', 'Tanggal', 'Kategori', 'Deskripsi', 'Foto', 'Durasi', 'Status']);
    headerRow.eachCell(cell => {
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4F46E5' } };
      cell.alignment = { vertical: 'middle', horizontal: 'center' };
    });

    dataToExport.forEach((r, idx) => {
      if (!includeFoto) {
        // Skip foto, isi kolom dengan '-'
        const row = sheet.addRow([idx + 1, r.tanggal, r.kategori, r.deskripsi, '-', r.durasi, r.status]);
        row.eachCell(cell => { cell.alignment = { vertical: 'middle', wrapText: true }; });
        row.getCell(1).alignment = { vertical: 'middle', horizontal: 'center' };
        row.getCell(5).alignment = { vertical: 'middle', horizontal: 'center' };
        row.getCell(6).alignment = { vertical: 'middle', horizontal: 'center' };
      } else {
        const img = photoBuffers[idx];
        const row = sheet.addRow([idx + 1, r.tanggal, r.kategori, r.deskripsi, img ? '' : '-', r.durasi, r.status]);
        row.height = 58; 
        row.eachCell(cell => { cell.alignment = { vertical: 'middle', wrapText: true }; });
        row.getCell(1).alignment = { vertical: 'middle', horizontal: 'center' };
        row.getCell(5).alignment = { vertical: 'middle', horizontal: 'center' };
        row.getCell(6).alignment = { vertical: 'middle', horizontal: 'center' };

        if (img) {
          const imageId = workbook.addImage({ buffer: img.buffer, extension: img.extension });
          sheet.addImage(imageId, {
            tl: { col: 4.15, row: row.number - 1 + 0.1 },
            ext: { width: 62, height: 62 },
            editAs: 'oneCell'
          });
        }
      }
    });

    updateExportLoaderProgress(98, 'Membuat file Excel...');
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    let fileName = `Laporan_${nama.replace(/\s+/g, '_')}`;
    if (currentTahun !== 'Semua') fileName += `_${currentTahun}`;
    if (currentTriwulan !== 'Semua') fileName += `_TW${currentTriwulan}`;
    if (currentFilter !== 'Semua') fileName += `_${currentFilter.replace(/\s+/g, '_')}`;
    link.download = fileName + '.xlsx';
    document.body.appendChild(link);
    
    updateExportLoaderProgress(100, 'Ekstraksi selesai!');
    await new Promise(resolve => setTimeout(resolve, 350));
    
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showToast('✅ Excel berhasil diekspor!', 'success');
  } catch (err) {
    showToast('❌ Gagal export: ' + err.message, 'error');
  } finally {
    hideExportLoader();
    btnExport.disabled = false;
    btnExport.innerHTML = originalText;
  }
}

// showToast() is provided globally by /assets/css/js/sonner.js

const style = document.createElement('style');
style.innerHTML = `
  @keyframes spin { 100% { transform: rotate(360deg); } } 
  @keyframes slideInToast { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
`;
document.head.appendChild(style);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>