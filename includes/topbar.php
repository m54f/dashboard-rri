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
      <span><?= htmlspecialchars($userLabel) ?></span>
    </div>

    <!-- Divider Vertikal -->
    <div class="topbar-divider"></div>

    <!-- Action Buttons -->
    <?php if ($role === 'admin'): ?>
      <a href="<?= $base ?>/pages/auth/logout.php" class="topbar-btn btn-logout" title="Keluar dari sistem">
        <i class="ri-logout-box-r-line"></i>
        <span class="btn-text">Keluar</span>
      </a>
    <?php else: ?>
      <a href="<?= $base ?>/pages/auth/login.php" class="topbar-btn btn-login" title="Masuk sebagai Admin">
        <i class="ri-login-box-line"></i>
        <span class="btn-text">Masuk</span>
      </a>
    <?php endif; ?>
  </div>
</div>