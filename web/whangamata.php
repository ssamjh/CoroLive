<!doctype html>
<html lang="en">

<?php $camera = 'whangamata';?>

<head>
    <?php $pageName = "Whangamata - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/<?php echo "$camSrc"; ?>/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whangamata Live Stream</h3>

    <div class="alert alert-danger text-center" role="alert">
This camera is currently faulty and will be fixed ASAP!
    </div>

    <br>

    <?php require 'player.php';?>

    <?php require 'live.php';?>

    <?php require 'footer.php';?>
</body>

</html>