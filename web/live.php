<script>
var config = {
    source: 'https://api.corolive.nz/hls/<?php echo "$camera"; ?>.m3u8', 
    poster: 'https://api.corolive.nz/<?php echo "$camera"; ?>/snap.webp',
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