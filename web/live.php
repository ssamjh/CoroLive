<?php
function isMobileDevice()
{
    return preg_match("/mobile/i", $_SERVER["HTTP_USER_AGENT"]);
}
if (isMobileDevice())
{
    $camSrc = "https://api.corolive.nz/$camera/hls-low/live.stream.m3u8";
}
else
{
    $camSrc = "https://api.corolive.nz/$camera/stream.m3u8";
}
?>

<script>
var config = {
    source: '<?php echo "$camSrc"; ?>',
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