<?php
$base = '..';
$pageTitle = 'Jadwal Shift';
$activeMenu = 'shift';

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

<style>
:root {
    --bg:#f8fafc;
    --card:#fff;
    --border:#e2e8f0;
    --text:#0f172a;
    --primary:#2563eb;
}

body {
    background:var(--bg);
    font-family:'Inter',sans-serif;
}

.page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:2rem;
}

.page-header h2 {
    margin:0;
    font-size:1.8rem;
    font-weight:800;
}

.page-header p {
    color:#64748b;
    margin-top:.5rem;
}


/* GRID GAMBAR SHIFT */

.shift-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:1.5rem;
}


.shift-card {
    background:white;
    border:1px solid var(--border);
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 10px rgba(0,0,0,.05);
    transition:.3s;
}


.shift-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 20px rgba(0,0,0,.08);
}


.shift-image {
    width:100%;
    aspect-ratio:4/5;
    background:#f1f5f9;
    overflow:hidden;
}


.shift-image img {
    width:100%;
    height:100%;
    object-fit:contain;
    transition:.4s;
}


.shift-card:hover img {
    transform:scale(1.03);
}


.shift-footer {
    padding:1rem 1.2rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
}


.shift-name {
    font-weight:700;
    color:var(--text);
}


.btn-view {
    background:#eff6ff;
    color:#2563eb;
    padding:.45rem .8rem;
    border-radius:8px;
    text-decoration:none;
    font-size:.85rem;
    font-weight:600;
}


.empty {
    background:white;
    border:1px dashed #cbd5e1;
    padding:4rem;
    text-align:center;
    border-radius:16px;
    color:#64748b;
}

.empty i {
    font-size:3rem;
    display:block;
    margin-bottom:1rem;
}
</style>


<div class="page-header">
    <div>
        <h2>Jadwal Shift</h2>
        <p>Lihat pembagian jadwal shift non operasional.</p>
    </div>
</div>


<?php if(empty($shiftImages)): ?>

<div class="empty">
    <i class="ri-image-line"></i>
    <h3>Belum ada gambar jadwal shift</h3>
    <p>
        Upload gambar jadwal ke folder:
        <br>
        <b>/assets/images/shift/</b>
    </p>
</div>


<?php else: ?>

<div class="shift-grid">

<?php foreach($shiftImages as $img): ?>

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
            class="btn-view"
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
