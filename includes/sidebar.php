<?php
/* Peta menu: key => [label, icon, link relatif dari $base] */
$menuOperasional = [
    'agenda'      => ['Daily Agenda',        'ri-calendar-line',          '/agenda/'],
    'checklist'   => ['Checklist Perangkat', 'ri-checkbox-circle-line',   '/checklist/'],
    'troubleshoot'=> ['Troubleshoot',        'ri-tools-line',             '/troubleshoot/'],
    'logbook'     => ['Logbook Digital',     'ri-book-2-line',            '/logbook/'],
    'peminjaman'  => ['Peminjaman Alat',     'ri-service-line',           '/peminjaman/'],
];
$menuDokumentasi = [
    'jurnal'   => ['Jurnal Kegiatan', 'ri-file-list-3-line', '/jurnal/'],
    'gallery'  => ['Gallery Youtube', 'ri-play-line',         '/gallery/'],
    'calendar' => ['Calendar Recap',  'ri-calendar-2-line',   '/calendar/'],
];
$menuPersonal = [
    'petugas'  => ['Petugas',        'ri-team-line',      '/petugas/'],
    'personal' => ['Jurnal Pribadi', 'ri-file-user-line', '/personal/'],
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
      <a class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" href="<?= $base ?>/dashboard.php"><i class="ri-dashboard-line"></i><span>Dashboard</span></a>
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
  <div class="sidebar-cta">
    <h4>Sistem Peminjaman Alat</h4>
    <p>Kelola peminjaman dan pengembalian alat siaran dengan mudah.</p>
    <a href="<?= $base ?>/peminjaman/">Buka Sistem <i class="ri-arrow-right-line"></i></a>
  </div>
</aside>
