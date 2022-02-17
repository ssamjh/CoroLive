<!doctype html>
<html lang="en">

<head>
    
<?php require 'head.php';?>

    <title>Whitianga - CoroLive</title>

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



    <?php include 'footer.php';?>

</body>

</html>