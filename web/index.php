<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>
    <?php $pageName = "CoroLive - The Coromandel's Live Webcam Network"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image" content="img/corolive512.webp" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h2 class="text-center">Pick your view!</h2>

    <br>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <?php
            $locations = [
                [
                    'name' => 'Whitianga',
                    'id' => 'whitianga',
                    'imgUrl' => 'https://api.corolive.nz/whitianga/snap.webp',
                ],
                [
                    'name' => 'Whangamata',
                    'id' => 'whangamata',
                    'imgUrl' => 'https://api.corolive.nz/whangamata/snap.webp',
                ],
                [
                    'name' => 'Thames',
                    'id' => 'thames',
                    'imgUrl' => 'https://api.corolive.nz/thames/snap.webp',
                ],
            ];

            foreach ($locations as $location) {
                echo "<div class='col-sm-3'>
                    <div class='card text-center'>
                        <div class='card-body'>
                            <a href='{$location['id']}'><img id='{$location['id']}-thumbnail'
                                    src='{$location['imgUrl']}?rand=" . rand() . "'
                                    draggable='false' class='card-img-top' alt='{$location['name']} picker image'></a>
                            <h5 class='card-title text-center'>{$location['name']}</h5>
                            <div class='center'>
                                <a href='{$location['id']}' class='btn btn-primary'>Live</a>
                                <a href='timelapse?camera={$location['id']}' class='btn btn-secondary'>Timelapse</a>
                            </div>
                        </div>
                    </div>
                </div>";
            }
            ?>
        </div>
    </div>

    <script>
        function refreshThumbnail(id, url) {
            var thumbnail = document.getElementById(id + '-thumbnail');
            thumbnail.src = url + '?rand=' + Math.random();
        }

        setInterval(function () {
            <?php
            foreach ($locations as $location) {
                echo "refreshThumbnail('{$location['id']}', '{$location['imgUrl']}');\n";
            }
            ?>
        }, 15000);
    </script>

    <?php require 'footer.php'; ?>

</body>

</html>