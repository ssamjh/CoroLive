<script>
var config = {
    source: '<?php echo "$camSrc"; ?>',
    poster: '<?php echo "$camPoster"; ?>',
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