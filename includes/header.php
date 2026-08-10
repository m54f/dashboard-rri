<?php
/**
 * includes/header.php
 * Wajib set variabel berikut SEBELUM include file ini:
 *   $base       = '.'  jika halaman ada di root, atau '..' jika di dalam subfolder
 *   $pageTitle  = judul halaman, contoh 'Daily Agenda'
 *   $activeMenu = key menu aktif, contoh 'agenda' (lihat includes/sidebar.php)
 */
if (!isset($base)) $base = '.';
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($activeMenu)) $activeMenu = 'dashboard';

// Load auth helper for role management
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — TMB RRI Malang</title>
<link rel="icon" href="../assets/images/icon/logo-rri.png" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<?php $cssPath = __DIR__ . '/../assets/css/style.css'; $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time(); ?>
<link href="<?= $base ?>/assets/css/style.css?v=<?= $cssVersion ?>" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= $base ?>/assets/css/js/sonner.js"></script>
<script>
  // Cegah flash putih saat buka halaman jika mode gelap aktif
  (function() {
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.classList.add('dark-mode');
    }
  })();
</script>
</head>
<body>
<div class="layout">
<?php include __DIR__ . '/sidebar.php'; ?>
  <main class="main">
<?php include __DIR__ . '/topbar.php'; ?>
  <div class="content-body">




<script>
  // Terapkan status sidebar tersimpan dari localStorage & cegah flash animasi (FOUC)
  (function() {
    document.body.classList.add('preload');
    if (localStorage.getItem('theme') === 'dark') {
      document.body.classList.add('dark-mode');
    }
    if (window.innerWidth > 768 && localStorage.getItem('sidebarHidden') === 'true') {
      document.body.classList.add('sidebar-hidden');
    }
    window.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        document.body.classList.remove('preload');
      }, 50);
    });
  })();

  // Handle Dark Mode Manual Toggle
  function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark-mode');
    if (document.body) {
      document.body.classList.toggle('dark-mode', isDark);
    }
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcons(isDark);
  }

  function updateThemeIcons(isDark) {
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
      icon.className = isDark ? 'ri-sun-line' : 'ri-moon-line';
    }
  }

  // Sync DOM & Icon on Load & Handle Scroll Elevation
  window.addEventListener('DOMContentLoaded', function() {
    const isDark = localStorage.getItem('theme') === 'dark';
    if (isDark) {
      document.documentElement.classList.add('dark-mode');
      if (document.body) document.body.classList.add('dark-mode');
    }
    updateThemeIcons(isDark);

    // Dynamic Scroll Elevation Shadow untuk Topbar Header
    function handleTopbarScroll() {
      const topbar = document.querySelector('.topbar');
      if (topbar) {
        if (window.scrollY > 10) {
          topbar.classList.add('scrolled');
        } else {
          topbar.classList.remove('scrolled');
        }
      }
    }
    handleTopbarScroll();
    window.addEventListener('scroll', handleTopbarScroll, { passive: true });
  });

  // Script untuk toggle sidebar
  function toggleSidebar() {
    if (window.innerWidth <= 768) {
      // Di HP: sidebar defaultnya hilang total (lihat CSS). Tap tombol ini
      // buka-tutup mode overlay (sidebar slide-in menimpa konten).
      document.body.classList.toggle('sidebar-mobile-expanded');
    } else {
      // Di laptop/desktop: toggle collapse (strip ikon) <-> expanded biasa.
      const isHidden = document.body.classList.toggle('sidebar-hidden');
      localStorage.setItem('sidebarHidden', isHidden ? 'true' : 'false');
    }
  }

  // Di HP, tap di luar sidebar (area konten yang ketutup) otomatis menutup overlay-nya
  document.addEventListener('click', function (e) {
    if (window.innerWidth > 768) return;
    if (!document.body.classList.contains('sidebar-mobile-expanded')) return;
    const sidebarEl = document.getElementById('sidebar');
    const isToggleBtn = e.target.closest('.menu-toggle');
    if (sidebarEl && !sidebarEl.contains(e.target) && !isToggleBtn) {
      document.body.classList.remove('sidebar-mobile-expanded');
    }
  });
</script>