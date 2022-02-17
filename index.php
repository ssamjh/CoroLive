<!doctype html>
<html lang="en">

<head>
    
<?php require 'head.php';?>

    <title>CoroLive - The Coromandel's Live Webcam Network</title>

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

    <br>

    <h2 class="text-center">Pick your view!</h2>

    <br>

    <div class="row justify-content-md-center">
        <div class="col-sm-3">
            <div class="card text-center">
                <div class="card-body">
                    <a href="whitianga"><img src="https://api.corolive.nz/whitianga/snap.jpg" draggable="false" class="card-img-top" alt="Whitianga picker image"></a>
                    <h5 class="card-title text-center">Whitianga</h5>
                    <div class="center">
                        <a href="whitianga" class="btn btn-primary">Live</a>
                        <a href="whitianga-timelapse" class="btn btn-secondary">Timelapse</a>
                        <a href="https://api.corolive.nz/whitianga/archive/" target="_blank" class="btn btn-info">Archive</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center">
                <div class="card-body">
                    <a href="whangamata"><img src="https://api.corolive.nz/whangamata/snap.jpg" draggable="false" class="card-img-top" alt="Whangamata picker image"></a>
                    <h5 class="card-title text-center">Whangamata</h5>
                    <div class="center">
                        <a href="whangamata" class="btn btn-primary">Live</a>
                        <a href="whangamata-timelapse" class="btn btn-secondary">Timelapse</a>
                        <a href="https://api.corolive.nz/whangamata/archive/" target="_blank" class="btn btn-info">Archive</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center">
                <div class="card-body">
                    <a href="thames"><img src="https://api.corolive.nz/thames/snap.jpg" draggable="false" class="card-img-top" alt="Thames picker image"></a>
                    <h5 class="card-title text-center">Thames</h5>
                    <div class="center">
                        <a href="thames" class="btn btn-primary">Live</a>
                        <a href="thames-timelapse" class="btn btn-secondary">Timelapse</a>
                        <a href="https://api.corolive.nz/thames/archive/" target="_blank" class="btn btn-info">Archive</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php';?>

</body>

</html>