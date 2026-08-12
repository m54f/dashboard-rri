<div class="topbar">
  <div class="topbar-left">
    <!-- Tombol Toggle Sidebar -->
    <button class="topbar-btn menu-toggle" onclick="toggleSidebar()" title="Buka/Tutup Menu">
      <i class="ri-menu-line"></i>
    </button>
    
    <!-- Search Box (Opsional, sudah didesain modern jika ingin diaktifkan) -->
    <!-- 
    <div class="search-box">
      <i class="ri-search-line"></i>
      <input type="text" placeholder="Cari kegiatan, logbook, atau perangkat...">
      <kbd>Ctrl K</kbd>
    </div> 
    -->
  </div>

  <div class="topbar-right">
    <?php
    // Display role badge
    $role = getRole();
    $userLabel = getUserLabel();
    $shortRoleLabel = ($role === 'admin') ? 'Admin' : 'Public';
    
    // Tentukan style badge berdasarkan role
    if ($role === 'admin') {
        $badgeClass = 'badge-admin';
        $badgeIcon = 'ri-shield-check-line';
    } else {
        $badgeClass = 'badge-guest';
        $badgeIcon = 'ri-global-line';
    }
    ?>

    <!-- Role Badge -->
    <div class="role-badge <?= $badgeClass ?>">
      <i class="<?= $badgeIcon ?>"></i>
      <span class="role-full-text"><?= htmlspecialchars($userLabel) ?></span>
      <span class="role-short-text"><?= htmlspecialchars($shortRoleLabel) ?></span>
    </div>

    <!-- Tombol Panduan Halaman (muncul hanya jika halaman punya tour) -->
    <button type="button" class="topbar-btn btn-tour-hint" id="btnStartTour"
            onclick="if(window.__tourSteps&&window.__tourKey)startTour(window.__tourSteps,window.__tourKey,true);"
            title="Panduan Halaman Ini"
            style="display:none;">
      <i class="ri-question-line"></i>
    </button>

    <!-- Tombol Mode Gelap/Terang -->
    <button type="button" class="topbar-btn btn-theme-toggle" onclick="toggleDarkMode()" title="Ganti Mode Terang/Gelap">
      <i class="ri-moon-line" id="themeToggleIcon"></i>
    </button>

    <!-- Divider Vertikal -->
    <div class="topbar-divider"></div>

    <!-- Action Buttons -->
    <?php if ($role === 'admin'): ?>
      <a href="<?= $base ?>/pages/auth/logout.php" class="topbar-btn btn-logout" title="Keluar dari sistem">
        <i class="ri-logout-box-r-line"></i>
        <span class="btn-text">Keluar</span>
      </a>
    <?php else: ?>
      <?php 
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $loginUrl = $base . '/pages/auth/login.php' . ($currentUri !== '' ? '?redirect=' . urlencode($currentUri) : '');
      ?>
      <a href="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>" class="topbar-btn btn-login" title="Masuk sebagai Admin">
        <i class="ri-login-box-line"></i>
        <span class="btn-text">Masuk</span>
      </a>
    <?php endif; ?>
    
    <!-- Link ke Telegram Cakra Bot -->
    <a href="https://t.me/CakraIT_bot" class="topbar-btn btn-telegram-text" title="Hubungi Tim Teknik (Cakra Bot)" target="_blank" rel="noopener noreferrer">
      <i class="ri-telegram-line"></i>
      <span class="btn-text">Cakra Bot</span>
    </a>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.__tourSteps) {
    var btn = document.getElementById('btnStartTour');
    if (btn) btn.style.display = '';
  }
});
</script>
