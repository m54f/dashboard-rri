<?php

$base = '..';
$pageTitle = 'Aplikasi RRI';
$activeMenu = 'aplikasi';

include __DIR__ . '/../includes/header.php';


// =====================================
// LINK APLIKASI
// Nama harus sama dengan nama gambar
// setelah nomor dihapus
// =====================================

$panelLinks = [

    'Srikandi' =>
    'https://srikandi.arsip.go.id/',

    'Te Track' =>
    'https://track.rri.go.id/',

    'Logger' =>
    'https://logger.rri.co.id/',

    'Audio Analyzer' =>
    'https://huggingface.co/spaces/dezikrie/rri-analyzer-audio',

    'Intranet' =>
    'https://intranet-v2.rri.go.id/auth/login',

    'CMS' =>
    'https://intranet-v2.rri.go.id/auth/login'
   
];

$panelDesc = [

    'Srikandi' => 'Aplikasi Administrasi Persuratan',
    'Te Track' => 'Aplikasi Pemancar TMB',
    'Logger' => 'Log Audio Programa',
    'Audio Analyzer' => 'Analisis Kualitas Sinyal',
    'Intranet' => 'Seluruh aplikasi RRI',
    'CMS' => 'Aplikasi Membuat Berita RRI',
    
];



// =====================================
// BACA GAMBAR FOLDER
// =====================================

$apps = [];


$folderPath = __DIR__ . '/../assets/images/aplikasi/';

$imagePath = $base . '/assets/images/aplikasi/';



if (is_dir($folderPath)) {


    $files = scandir($folderPath);



    foreach ($files as $file) {


        if ($file == '.' || $file == '..') {
            continue;
        }



        $ext = strtolower(
            pathinfo($file, PATHINFO_EXTENSION)
        );



        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
            continue;
        }



        $filename = pathinfo(
            $file,
            PATHINFO_FILENAME
        );



        // hapus nomor depan
        // 01-Srikandi
        // menjadi Srikandi

        $name = preg_replace(
            '/^[0-9]+[-_ ]*/',
            '',
            $filename
        );



        $name = ucwords(
            str_replace(
                ['-', '_'],
                ' ',
                $name
            )
        );



        $apps[] = [

            'name' => $name,

            'desc' => $panelDesc[$name],

            'image' => $file,


            'url' =>
            $panelLinks[$name]
                ??
                ($imagePath . $file)

        ];
    }
}



// urut sesuai nomor file

usort($apps, function ($a, $b) {

    return strcmp(
        $a['image'],
        $b['image']
    );
});




// 3 card setiap slide

$appSlides = array_chunk($apps, 3);


?>



<style>
    .panel-header {

        margin-bottom: 2rem;

    }


    .panel-header h2 {

        font-size: 1.8rem;

        font-weight: 800;

        color: #0f172a;

    }


    .panel-header p {

        color: #64748b;

    }



    .panel-grid {

        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 1.5rem;

    }




    .panel-card {

        background: white;

        border: 1px solid #e2e8f0;

        border-radius: 16px;

        overflow: hidden;

        box-shadow:
            0 1px 3px rgba(0, 0, 0, .06);

        transition: .3s;

        display: flex;

        flex-direction: column;

    }



    .panel-card:hover {

        transform: translateY(-5px);

        box-shadow:
            0 10px 20px rgba(0, 0, 0, .1);

    }




    .panel-image {

        height: 170px;

        display: flex;

        align-items: center;

        justify-content: center;

        padding: 20px;

        background: #f8fafc;

    }




    .panel-image img {

        max-width: 60%;

        max-height: 120px;

        object-fit: contain;

    }




    .panel-content {

        padding: 15px;

        text-align: center;

        flex: 1;

    }



    .panel-content h5 {

        font-weight: 700;

    }



    .panel-content p {

        font-size: .85rem;

        color: #64748b;

    }



    .panel-footer {

        text-align: center;

        padding-bottom: 20px;

    }




    .btn-panel {

        background: #2563eb;

        color: white;

        padding:
            8px 18px;

        border-radius: 10px;

        text-decoration: none;

        font-size: .85rem;

        font-weight: 600;

        display: inline-flex;

        align-items: center;

        gap: 5px;

    }


    .btn-panel:hover {

        background: #1d4ed8;

        color: white;

    }



    @media(max-width:992px) {

        .panel-grid {

            grid-template-columns:
                repeat(2, 1fr);

        }

    }



    @media(max-width:600px) {

        .panel-grid {

            grid-template-columns: 1fr;

        }

    }
</style>




<div class="panel-header">

    <h2>
        Panel Pelayanan
    </h2>

    <p>
        Akses cepat aplikasi dan layanan operasional TMB.
    </p>

</div>





<?php if (empty($apps)): ?>


    <div class="empty-state">

        <i class="ri-image-line"></i>

        <h3>
            Belum ada gambar
        </h3>

        <p>
            Upload gambar ke:
            <br>
            <b>
                assets/images/panel/
            </b>
        </p>

    </div>



<?php else: ?>



    <div id="appCarousel"
        class="carousel slide"
        data-bs-ride="carousel">


        <div class="carousel-inner">



            <?php foreach ($appSlides as $i => $slide): ?>



                <div class="carousel-item 
<?= $i == 0 ? 'active' : '' ?>">



                    <div class="panel-grid">



                        <?php foreach ($slide as $app): ?>



                            <div class="panel-card">



                                <div class="panel-image">


                                    <img src="<?= $imagePath . htmlspecialchars($app['image']) ?>">


                                </div>




                                <div class="panel-content">


                                    <h5>
                                        <?= htmlspecialchars($app['name']) ?>
                                    </h5>


                                    <p>
                                        <?= htmlspecialchars($app['desc']) ?>
                                    </p>


                                </div>




                                <div class="panel-footer">


                                    <a href="<?= htmlspecialchars($app['url']) ?>"
                                        target="_blank"
                                        class="btn-panel">


                                        <i class="ri-external-link-line"></i>

                                        Lihat Detail


                                    </a>


                                </div>



                            </div>



                        <?php endforeach; ?>



                    </div>


                </div>



            <?php endforeach; ?>



        </div>



        <button class="carousel-control-prev"
            type="button"
            data-bs-target="#appCarousel"
            data-bs-slide="prev">

            <span class="carousel-control-prev-icon"></span>

        </button>


        <button class="carousel-control-next"
            type="button"
            data-bs-target="#appCarousel"
            data-bs-slide="next">

            <span class="carousel-control-next-icon"></span>

        </button>



    </div>



<?php endif; ?>



<?php include __DIR__ . '/../includes/footer.php'; ?>