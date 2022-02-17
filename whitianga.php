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

    <div class="container-md">
        <script src="https://cdn.jsdelivr.net/npm/clappr@0.3.12/dist/clappr.min.js"></script>
        <div id="player"></div>
        <script>
        var config = {
            source: '//api.corolive.nz/whitianga/stream.m3u8',
            poster: '//api.corolive.nz/whitianga/snap.jpg',
            parentId: '#player',
            watermark: "//corolive.nz/watermark.png",
            position: 'bottom-right',
            mute: true,
            autoPlay: true,
            actualLiveTime: true,
            hideVolumeBar: true,
            events: {
                onReady: function() {
                    var plugin = this.getPlugin('click_to_pause');
                    plugin && plugin.disable();
                },
            },
        };

        var player = new window.Clappr.Player(config);

        function resizePlayer() {
            var aspectRatio = 9 / 16,
                newWidth = document.getElementById('player').parentElement.offsetWidth,
                newHeight = 2 * Math.round(newWidth * aspectRatio / 2);
            player.resize({
                width: newWidth,
                height: newHeight
            });
        }

        resizePlayer();
        window.onresize = resizePlayer;
        </script>
    </div>

    <?php include 'footer.php';?>

</body>

</html>