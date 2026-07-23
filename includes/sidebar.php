<?php
/* Peta menu: key => [label, icon, link relatif dari $base] */
$menuOperasional = [
    'agenda'      => ['Daily Agenda',        'ri-calendar-line',          '/pages/agenda.php'],
    'checklist'   => ['Checklist Perangkat', 'ri-checkbox-circle-line',   '/pages/checklist.php'],
    'troubleshoot'=> ['Troubleshoot',        'ri-tools-line',             '/pages/troubleshoot.php'],
    'pemadaman' => ['Log Pemadaman PLN',   'ri-flashlight-line',        '/pages/pemadaman.php'],
    'peminjaman'  => ['Peminjaman Alat',     'ri-service-line',           '/pages/peminjaman.php'],
    'inventaris'  => ['Inventaris',          'ri-book-line',              '/pages/inventaris.php'],
];
$menuDokumentasi = [
    'jurnal'   => ['Jurnal Kegiatan', 'ri-file-list-3-line', '/pages/jurnal.php'],
    'gallery'  => ['Rekap Youtube',   'ri-play-line',        '/pages/gallery.php'],
    'calendar' => ['Calendar Recap',  'ri-calendar-2-line',  '/pages/calendar.php'],
];
$menuPersonal = [
    'petugas'  => ['Petugas', 'ri-team-line', '/pages/petugas.php'],
];

function render_menu_group($items, $base, $activeMenu) {
    foreach ($items as $key => $m) {
        [$label, $icon, $path] = $m;
        $cls = $key === $activeMenu ? 'menu-item active' : 'menu-item';
        echo '<a class="' . $cls . '" href="' . $base . $path . '"><i class="' . $icon . '"></i><span>' . htmlspecialchars($label) . '</span></a>';
    }
}
?>
<aside class="sidebar" id="sidebar">

  <div class="sidebar-header">
    <div class="logo-mark"><img src="https://ppid.rri.go.id/theme_v2/img/logo/logo-23.png" alt="Logo RRI"></div>
    <div>
      <h1>TMB RRI MALANG</h1>
      <span>Divisi Teknologi &amp; Media Baru</span>
    </div>
  </div>
  
  <nav class="sidebar-menu">
    <div class="menu-section">
      <a class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" href="<?= $base ?>/pages/dashboard.php"><i class="ri-dashboard-line"></i><span>Dashboard</span></a>
    </div>
    <div class="menu-section">
      <div class="menu-title">Operasional</div>
      <?php render_menu_group($menuOperasional, $base, $activeMenu); ?>
    </div>
    <div class="menu-section">
      <div class="menu-title">Dokumentasi</div>
      <?php render_menu_group($menuDokumentasi, $base, $activeMenu); ?>
    </div>
    <div class="menu-section">
      <div class="menu-title">Personal</div>
      <?php render_menu_group($menuPersonal, $base, $activeMenu); ?>
    </div>
  </nav>


<!-- FOOTER / WATERMARK PREMIUM (Dapat Diklik) -->
<div class="sidebar-footer">
  <div class="pkl-badge" onclick="openCopyrightModal()" style="cursor: pointer;" title="Klik untuk melihat tim pengembang">
    <div class="badge-text">
      <span class="badge-title">Dikembangkan oleh</span>
      <span class="badge-subtitle">Tim PKL UIN Malang</span>
    </div>
  </div>
</div>
</aside>

<!-- ===== MODAL COPYRIGHT TIM PKL ===== -->
<div class="modal-overlay" id="copyrightModal">
<div class="modal-content" style="max-width: 520px; text-align: center; padding: 2rem;">
  
  <!-- Tombol Close -->
  <button class="modal-close" onclick="closeCopyrightModal()" style="position: absolute; right: 20px; top: 20px; font-size: 24px;">&times;</button>
  
  <!-- Header Modal -->
  <h3 class="modal-title" style="font-size: 1.25rem; margin-bottom: 0.5rem;">Tim Pengembang</h3>
  <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">Proyek Magang (PKL) - UIN Maulana Malik Ibrahim Malang</p>
  
  <!-- Grid 4 Anggota -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: left; margin-bottom: 1.5rem;">
    
    <!-- Anggota 1 -->
    <div style="background: var(--bg-color); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
      <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">Reihan</div>
      <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">Programmer</div>
    </div>
    
    <!-- Anggota 2 -->
    <div style="background: var(--bg-color); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
      <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">Salis</div>
      <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">Programmer</div>
    </div>
    
    <!-- Anggota 3 -->
    <div style="background: var(--bg-color); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
      <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">Zidane</div>
      <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">Programmer</div>
    </div>
    
    <!-- Anggota 4 -->
    <div style="background: var(--bg-color); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
      <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">Salman</div>
      <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 4px;">Programmer</div>
    </div>

  </div>

  <!-- Footer Copyright -->
  <div style="border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 0.5rem;">
    <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.6;">
      © <?= date('Y') ?> <strong>Tim PKL UIN Malang</strong>.<br>
      Seluruh hak cipta dilindungi undang-undang.<br>
      <span style="color: var(--primary); font-weight: 600;">Dibuat dengan ❤️ untuk TMB RRI Malang</span>
    </p>
  </div>
  
</div>
</div>

<!-- Script untuk Modal -->
<script>
function openCopyrightModal() {
document.getElementById('copyrightModal').classList.add('active');
}

function closeCopyrightModal() {
document.getElementById('copyrightModal').classList.remove('active');
}

// Tutup modal jika klik di area gelap (overlay)
document.getElementById('copyrightModal').addEventListener('click', function(e) {
if (e.target === this) {
  closeCopyrightModal();
}
});

// Tutup modal dengan tombol ESC
document.addEventListener('keydown', function(e) {
if (e.key === 'Escape') {
  closeCopyrightModal();
}
});
</script>
