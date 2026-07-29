<div class="topbar">
  <div class="topbar-left">
    <!-- Tombol ini akan berfungsi untuk buka/tutup sidebar di semua ukuran layar -->
    <button class="menu-toggle" onclick="toggleSidebar()" title="Buka/Tutup Menu">
      <i class="ri-menu-line"></i>
    </button>
    <!-- <div class="search-box">
      <i class="ri-search-line"></i>
      <input type="text" placeholder="Cari kegiatan, logbook, atau perangkat...">
    </div> -->
  </div>
  <div class="topbar-right">
    <?php
    // Display role badge
    $role = getRole();
    $userLabel = getUserLabel();
    $badgeClass = $role === 'admin' ? 'role-badge-admin' : 'role-badge-open';
    $badgeIcon = $role === 'admin' ? 'ri-shield-check-line' : 'ri-global-line';
    ?>
    <div class="<?= $badgeClass ?>">
      <i class="<?= $badgeIcon ?>"></i>
      <span><?= $userLabel ?></span>
    </div>
    
    <?php if ($role === 'admin'): ?>
      <!-- Logout button for Admin -->
      <a href="<?= $base ?>/pages/auth/logout.php" class="icon-btn" title="Logout" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="ri-logout-box-line"></i>
      </a>
    <?php else: ?>
      <!-- Login button for Open mode -->
      <a href="<?= $base ?>/pages/auth/login.php" class="icon-btn" title="Login sebagai Admin" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="ri-login-box-line"></i>
      </a>
    <?php endif; ?>
  </div>
</div>