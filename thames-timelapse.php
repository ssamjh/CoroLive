<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Thames Timelapse - CoroLive</title>

    <meta property="og:image" content="https://api.corolive.nz/thames/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Thames Timelapse</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php require 'footer.php';?>
</body>

</html>