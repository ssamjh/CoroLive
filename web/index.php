<!doctype html>
<html lang="en">

<head>
    <?php $pageName = "CoroLive - The Coromandel's Live Webcam Network"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image" content="img/corolive512.webp" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h2 class="text-center">Pick your view!</h2>

    <br>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-sm-3">
                <div class="card text-center">
                    <div class="card-body">
                        <a href="whitianga"><img id="whitianga-thumbnail" src="https://api.corolive.nz/imgproxy/S5fvR7DSxAnpNu1Os0xd37tAiFNTAR-l1bpoqPvPTRY/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovd2hpdGlhbmdhL3NuYXAud2VicA.webp?rand=<?php echo (rand()); ?>" draggable="false" class="card-img-top" alt="Whitianga picker image"></a>
                        <h5 class="card-title text-center">Whitianga</h5>
                        <div class="center">
                            <a href="whitianga" class="btn btn-primary">Live</a>
                            <a href="whitianga-timelapse" class="btn btn-secondary">Timelapse</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card text-center">
                    <div class="card-body">
                        <a href="whangamata"><img id="whangamata-thumbnail" src="https://api.corolive.nz/imgproxy/9nD2ByfvIPnevn470C9-JJ6lt62L7STOasLq6QMsFak/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovd2hhbmdhbWF0YS9zbmFwLndlYnA.webp?rand=<?php echo (rand()); ?>" draggable="false" class="card-img-top" alt="Whangamata picker image"></a>
                        <h5 class="card-title text-center">Whangamata</h5>
                        <div class="center">
                            <a href="whangamata" class="btn btn-primary">Live</a>
                            <a href="whangamata-timelapse" class="btn btn-secondary">Timelapse</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="card text-center">
                    <div class="card-body">
                        <a href="thames"><img id="thames-thumbnail" src="https://api.corolive.nz/imgproxy/e7DkeRUycsd3to1M7TU2yHxBWK-GvvAiMus8jWNBmDM/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovdGhhbWVzL3NuYXAud2VicA.webp?rand=<?php echo (rand()); ?>" draggable="false" class="card-img-top" alt="Thames picker image"></a>
                        <h5 class="card-title text-center">Thames</h5>
                        <div class="center">
                            <a href="thames" class="btn btn-primary">Live</a>
                            <a href="thames-timelapse" class="btn btn-secondary">Timelapse</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        setInterval(function() {
            var whitiangaThumbnail = document.getElementById('whitianga-thumbnail');
            whitiangaThumbnail.src =
                'https://api.corolive.nz/imgproxy/S5fvR7DSxAnpNu1Os0xd37tAiFNTAR-l1bpoqPvPTRY/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovd2hpdGlhbmdhL3NuYXAud2VicA.webp?rand=' +
                Math.random();

            var whangamataThumbnail = document.getElementById('whangamata-thumbnail');
            whangamataThumbnail.src =
                'https://api.corolive.nz/imgproxy/9nD2ByfvIPnevn470C9-JJ6lt62L7STOasLq6QMsFak/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovd2hhbmdhbWF0YS9zbmFwLndlYnA.webp?rand=' +
                Math.random();

            var thamesThumbnail = document.getElementById('thames-thumbnail');
            thamesThumbnail.src =
                'https://api.corolive.nz/imgproxy/e7DkeRUycsd3to1M7TU2yHxBWK-GvvAiMus8jWNBmDM/rs:fit::360:0/g:no/aHR0cHM6Ly9hcGkuY29yb2xpdmUubnovdGhhbWVzL3NuYXAud2VicA.webp?rand=' +
                Math.random();
        }, 15000);
    </script>

    <?php require 'footer.php'; ?>

</body>

</html>