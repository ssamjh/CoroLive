<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Whitianga - CoroLive</title>

    <meta property="og:image" content="https://api.corolive.nz/whitianga/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whitianga Live Stream</h3>

    <br>

    <?php $camera = 'whitianga';?>

    <?php require 'player.php';?>

    <?php require 'live.php';?>

    <?php require 'footer.php';?>
</body>

</html>