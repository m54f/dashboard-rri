
<?php
$base = '..';
$pageTitle = 'Juknis TMB LPP RRI Malang';
$activeMenu = 'juknis';

include __DIR__ . '/../includes/header.php';

$notionEmbedUrl = 'https://tmbrrimlg.notion.site/ebd//3631ca6157e180f3a719d95c17f7143f';
?>

<div class="page-head">
    <div>
        <h2>
            <i class="ri-book-open-line"></i>
            Juknis TMB RRI MLG
        </h2>

        <p>
            Petunjuk teknis TMB LPP RRI Malang.
        </p>
    </div>
</div>

<div class="notion-wrapper">
    <iframe
        src="<?= htmlspecialchars($notionEmbedUrl, ENT_QUOTES, 'UTF-8') ?>"
        width="100%"
        height="600"
        frameborder="0"
        allowfullscreen>
    </iframe>
</div>

<style>
    .notion-wrapper {
        width: 100%;
        height: calc(100vh - 180px);
        min-height: 700px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .notion-wrapper iframe {
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
    }

    @media (max-width: 768px) {
        .notion-wrapper {
            height: calc(100vh - 150px);
            min-height: 600px;
            border-radius: 8px;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

