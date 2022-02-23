<!doctype html>
<html lang="en">

<head>
    <?php $pageName = "Thames Timelapse - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/thames/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Thames Timelapse</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php $startDate = '2021-05-06';?>

    <?php require 'timelapse-picker.php';?>

    <?php require 'footer.php';?>
</body>

</html>