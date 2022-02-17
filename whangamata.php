<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Whangamata - CoroLive</title>

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

    <h3 class="text-center">Whangamata Live Stream</h3>

    <br>

    <?php $camera = 'whangamata';?>

    <?php require 'player.php';?>

    <?php require 'live.php';?>

    <?php require 'footer.php';?>
</body>

</html>