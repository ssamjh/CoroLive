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

    <div class="alert alert-danger text-center" role="alert">
This camera is currently faulty and will be fixed ASAP!
    </div>

    <br>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php $startDate = '2021-03-17';?>

    <?php require 'timelapse-picker.php';?>

    <?php require 'footer.php';?>
</body>

</html>