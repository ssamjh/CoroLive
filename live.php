<script>
var config = {
    source: '//api.corolive.nz/<?php echo "$camera"; ?>/stream.m3u8',
    poster: '//api.corolive.nz/<?php echo "$camera"; ?>/snap.jpg',
    parentId: '#player',
    watermark: "//corolive.nz/watermark.png",
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