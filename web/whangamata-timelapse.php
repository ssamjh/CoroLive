<!doctype html>
<html lang="en">

<?php $camera = 'whangamata';?>

<head>
    <?php $pageName = "Whangamata Timelapse - CoroLive";?>

    <?php require 'head.php';?>

    <?php require 'og-image.php';?>

    <meta property="og:image" content="<?php echo "$camOgURL"; ?>" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whangamata Timelapse</h3>

    <br>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php $startDate = '2021-03-17';?>

    <?php require 'timelapse-picker.php';?>

    <?php require 'footer.php';?>
</body>

</html>