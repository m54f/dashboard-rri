<?php
$base = '..';
$pageTitle = 'Jadwal Shift';
$activeMenu = 'jadwal';

include __DIR__ . '/../includes/header.php';

// Folder gambar shift
$shiftFolder = __DIR__ . '/../assets/images/shift/';
$shiftImages = [];

if (is_dir($shiftFolder)) {
    $files = glob($shiftFolder . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    foreach ($files as $file) {
        $shiftImages[] = [
            'name' => basename($file),
            'url'  => $base . '/assets/images/shift/' . basename($file)
        ];
    }
}
?>

<div class="page-head">
    <div>
        <h2>Jadwal Shift</h2>
        <p>Lihat pembagian jadwal shift non operasional.</p>
    </div>
</div>

<?php if (empty($shiftImages)): ?>

<div class="card empty-shift-state">
    <i class="ri-image-line"></i>
    <h3>Belum ada gambar jadwal shift</h3>
    <p>
        Upload gambar jadwal ke folder:
        <br>
        <code>/assets/images/shift/</code>
    </p>
</div>

<?php else: ?>

<div class="shift-grid">
<?php foreach ($shiftImages as $img): ?>

<div class="shift-card">
    <div class="shift-image">
        <img 
            src="<?= htmlspecialchars($img['url']) ?>" 
            alt="Jadwal Shift"
        >
    </div>

    <div class="shift-footer">
        <div class="shift-name">
            <?= htmlspecialchars(pathinfo($img['name'], PATHINFO_FILENAME)) ?>
        </div>

        <a 
            class="btn-view-shift"
            href="<?= htmlspecialchars($img['url']) ?>"
            target="_blank"
        >
            <i class="ri-eye-line"></i>
            Lihat
        </a>
    </div>
</div>

<?php endforeach; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
