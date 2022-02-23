<?php require 'player.php';?>

<script>
var config = {
    source: '//api.corolive.nz/<?php echo $_GET['camera']; ?>/stream.m3u8',
    poster: '//api.corolive.nz/<?php echo $_GET['camera']; ?>/snap.webp',
    parentId: '#player',
    watermark: "//corolive.nz/img/watermark.webp",
    position: 'bottom-right',
    watermarkLink: 'https://corolive.nz',
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