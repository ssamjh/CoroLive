<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php
$allowedCameras = ['whitianga', 'whangamata', 'thames'];
$camera = $_GET['camera'] ?? null;

if (!in_array($camera, $allowedCameras)) {
    header("Location: /");
    exit;
}

$cameraCapitalized = ucfirst($camera);
$pageName = "$cameraCapitalized Image Only - CoroLive";
$camStill = "https://api.corolive.nz/{$camera}/snap.webp";
?>

<head>
    <?php require 'head.php'; ?>
    <meta property="og:image" content="<?php echo $camStill; ?>" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">
        <?php echo $cameraCapitalized; ?> Image Only
    </h3>

    <br>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <div class="ratio ratio-16x9">
                    <img id="image-only" src="<?php echo $camStill; ?>?=<?php echo rand(); ?>">
                </div>
            </div>
        </div>
    </div>

    <script>
        setInterval(function () {
            var myImageElement = document.getElementById('image-only');
            myImageElement.src = '<?php echo $camStill; ?>?=' + Math.random();
        }, 15000);
    </script>

    <?php require 'footer.php'; ?>
</body>

</html>