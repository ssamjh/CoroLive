<!doctype html>
<html lang="en">

<head>
    <?php $pageName = "Thames Image Only - CoroLive";?>

    <?php require 'head.php';?>

    <meta property="og:image" content="https://api.corolive.nz/thames/snap.webp" />
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Thames Image Only</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'imageonly.php';?>

    <?php require 'footer.php';?>
</body>

</html>