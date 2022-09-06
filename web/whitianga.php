<!doctype html>
<html lang="en">

<?php $camera = 'whitianga'; ?>

<head>
    <?php $pageName = "Whitianga - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image" content="https://api.corolive.nz/<?php echo "$camera"; ?>/snap.webp" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">Whitianga Live Stream</h3>

    <br>

    <?php require 'player.php'; ?>

    <?php require 'live.php'; ?>

    <?php require 'live-picker.php'; ?>

    <?php require 'footer.php'; ?>
</body>

</html>