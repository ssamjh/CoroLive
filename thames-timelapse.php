<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Thames Timelapse - CoroLive</title>

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

    <h3 class="text-center">Thames Timelapse</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'player.php';?>

    <?php require 'timelapse.php';?>

    <?php require 'footer.php';?>
</body>

</html>