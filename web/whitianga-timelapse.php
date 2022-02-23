<!doctype html>
<html lang="en">

<head>
    <?php $pageName = "Whitianga Timelapse - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/whitianga/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whitianga Timelapse</h3>

    <br>

    <?php $camera = 'whitianga';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php $startDate = '2019-03-17';?>

    <?php require 'timelapse-picker.php';?>

    <?php require 'footer.php';?>
</body>

</html>