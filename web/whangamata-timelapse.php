<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Whangamata Timelapse - CoroLive</title>

    <meta property="og:image" content="https://api.corolive.nz/whangamata/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whangamata Timelapse</h3>

    <br>

    <?php $camera = 'whangamata';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php $startDate = '2021-03-17';?>

    <?php require 'timelapse-picker.php';?>

    <?php require 'footer.php';?>
</body>

</html>