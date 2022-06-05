<!doctype html>
<html lang="en">

<?php $camera = 'whitianga';?>

<head>
    <?php $pageName = "Whitianga Image Only - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/<?php echo "$camera"; ?>/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whitianga Image Only</h3>

    <br>

    <?php require 'imageonly.php';?>

    <?php require 'footer.php';?>
</body>

</html>