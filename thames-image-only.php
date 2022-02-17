<!doctype html>
<html lang="en">

<head>

    <?php require 'head.php';?>

    <title>Thames Image Only - CoroLive</title>

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

    <h3 class="text-center">Thames Image Only</h3>

    <br>

    <?php $camera = 'thames';?>

    <?php require 'imageonly.php';?>

    <?php require 'footer.php';?>
</body>

</html>