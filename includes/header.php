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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — TMB RRI Malang</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link href="<?= $base ?>/assets/css/style.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="layout">
<?php include __DIR__ . '/sidebar.php'; ?>
  <main class="main">
<?php include __DIR__ . '/topbar.php'; ?>
