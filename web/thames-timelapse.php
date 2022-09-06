<!doctype html>
<html lang="en">

<?php $camera = 'thames'; ?>

<head>
    <?php $pageName = "Thames Timelapse - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <?php require 'og-image.php'; ?>

    <meta property="og:image" content="<?php echo "$camOgURL"; ?>" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">Thames Timelapse</h3>

    <br>

    <?php require 'player.php'; ?>

    <?php require 'timelapse.php'; ?>

    <?php $startDate = '2021-05-06'; ?>

    <?php require 'timelapse-picker.php'; ?>

    <?php require 'footer.php'; ?>
</body>

</html>