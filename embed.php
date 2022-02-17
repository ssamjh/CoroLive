<?php require 'player.php';?>

<script>
var config = {
    source: 'https://api.corolive.nz/<?php echo $_GET['camera']; ?>/stream.m3u8',
    poster: 'https://api.corolive.nz/<?php echo $_GET['camera']; ?>/snap.jpg',
    parentId: '#player',
    watermark: "https://corolive.nz/img/watermark.webp",
    position: 'top-right',
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