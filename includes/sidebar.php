<?php
/* Peta menu: key => [label, icon, link relatif dari $base] */
$menuOperasional = [
    'agenda'      => ['Daily Agenda',        'ri-calendar-line',          '/pages/agenda.php'],
    'checklist'   => ['Checklist Perangkat', 'ri-checkbox-circle-line',   '/pages/checklist.php'],
    'troubleshoot'=> ['Troubleshoot',        'ri-tools-line',             '/pages/troubleshoot.php'],
    'Maintenance' => ['Log Pemadaman PLN', 'ri-flashlight-line', '/pages/pemadaman.php'],
    'peminjaman'  => ['Peminjaman Alat',     'ri-service-line',           '/pages/peminjaman.php'],
    'inventaris'  => ['Inventaris',     'ri-book-shelf-line',           '/pages/inventaris.php'],
];
$menuDokumentasi = [
    'jurnal'   => ['Jurnal Kegiatan', 'ri-file-list-3-line', '/pages/jurnal.php'],
    'gallery'  => ['Rekap Youtube', 'ri-play-line',         '/pages/gallery.php'],
    'calendar' => ['Calendar Recap',  'ri-calendar-2-line',   '/pages/calendar.php'],
];
$menuPersonal = [
    'petugas'  => ['Petugas',        'ri-team-line',      '/pages/petugas.php'],
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
</aside>
