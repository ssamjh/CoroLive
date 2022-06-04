<?php
$camSrc = "//api.corolive.nz/hls/$_GET[camera].m3u8";
$camPoster = "//api.corolive.nz/$_GET[camera]/snap.webp";

require 'player.php';
?>


<script>
var config = {
    source: '<?php echo "$camSrc"; ?>',
    poster: '<?php echo "$camPoster"; ?>',
    watermark: "//corolive.nz/img/watermark.webp",
    position: 'bottom-right',
    watermarkLink: 'https://corolive.nz',
    parentId: '#player',
    position: 'bottom-right',
    mute: true,
    autoPlay: true,
    actualLiveTime: true,
    hideVolumeBar: true,
    width: '100%',
    height: '100%',
    events: {
        onReady: function() {
            var plugin = this.getPlugin('click_to_pause');
            plugin && plugin.disable();
        },
    },
};

var player = new window.Clappr.Player(config);
</script>