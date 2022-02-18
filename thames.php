<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Thames - CoroLive</title>

    <meta property="og:image" content="https://api.corolive.nz/thames/snap.webp" />

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

    <h3 class="text-center">Thames Live Stream</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'player.php';?>

    <?php require 'live.php';?>

    <?php require 'footer.php';?>
</body>

</html>