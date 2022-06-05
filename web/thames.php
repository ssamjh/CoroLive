<!doctype html>
<html lang="en">

<?php $camera = 'thames';?>

<head>
    <?php $pageName = "Thames - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/<?php echo "$camSrc"; ?>/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Thames Live Stream</h3>

    <br>

    <?php require 'player.php';?>

    <?php require 'live.php';?>

    <?php require 'footer.php';?>
</body>

</html>