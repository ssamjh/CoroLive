<head>
    <?php $cameraCapitalized = ucfirst($camera); ?>
    <?php $pageName = "$cameraCapitalized - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image"
        content="https://api.corolive.nz/<?php echo $camera; ?>/snap.webp?rand=<?php echo rand(); ?>" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">
        <?php echo $cameraCapitalized; ?> Live Stream
    </h3>

    <br>

    <?php require 'player.php'; ?>

    <script>
        var config = {
            source: 'https://api.corolive.nz/hls/<?php echo $camera; ?>_high.m3u8',
            poster: 'https://api.corolive.nz/<?php echo $camera; ?>/snap.webp?rand=<?php echo rand(); ?>',
            parentId: '#player',
            position: 'bottom-right',
            mute: true,
            autoPlay: true,
            actualLiveTime: true,
            hideVolumeBar: true,
            width: '100%',
            height: '100%',
            events: {
                onReady: function () {
                    var plugin = this.getPlugin('click_to_pause');
                    plugin && plugin.disable();
                },
            },
        };

        var player = new window.Clappr.Player(config);
    </script>


    <?php require 'footer.php'; ?>
</body>