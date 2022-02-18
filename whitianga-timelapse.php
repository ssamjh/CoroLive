<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Whitianga Timelapse - CoroLive</title>

    <meta property="og:image" content="https://api.corolive.nz/whitianga/snap.webp" />

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        $('body').on('contextmenu', 'img', function(e) {
            return false;
        });
    });
    </script>
</head>

<body>
    <?php require 'navbar.php';?>

    <h3 class="text-center">Whitianga Timelapse</h3>

    <br>

    <?php $camera = 'whitianga';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php require 'footer.php';?>
</body>

</html>