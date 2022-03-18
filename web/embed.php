<?php
function isMobileDevice()
{
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo
|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
if (isMobileDevice())
{
    $camSrc = "//api.corolive.nz/$_GET[camera]/hls-low/live.stream.m3u8";
}
else
{
    $camSrc = "//api.corolive.nz/$_GET[camera]/stream.m3u8";
}
?>

<?php require 'player.php';?>

<script>
var config = {
    source: '<?php echo $camSrc; ?>',
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