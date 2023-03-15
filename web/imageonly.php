<?php require 'local-security.php'; ?>

<head>
    <?php $cameraCapitalized = ucfirst($camera); ?>
    <?php $pageName = "$cameraCapitalized Image Only - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image" content="https://api.corolive.nz/<?php echo "$camera"; ?>/snap.webp" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">
        <?php echo "$cameraCapitalized"; ?> Image Only
    </h3>

    <br>

    <?php
    $camStill = "https://api.corolive.nz/$camera/snap.webp";
    ?>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <div class="ratio ratio-16x9">
                    <img id="image-only" src="<?php echo "$camStill"; ?>?=<?php echo (rand()); ?>">
                </div>
            </div>
        </div>
    </div>

    <script>
        setInterval(function () {
            var myImageElement = document.getElementById('image-only');
            myImageElement.src = '<?php echo "$camStill"; ?>?=' + Math.random();
        }, 15000);
    </script>

    <?php require 'footer.php'; ?>
</body>

</html>