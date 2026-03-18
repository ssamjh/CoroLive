<?php
$allowedCameras = ['whitianga', 'whangamata', 'thames'];
$camera = $_GET['camera'] ?? null;

if (!in_array($camera, $allowedCameras)) {
    http_response_code(400);
    exit;
}

$camSrc = "https://api.corolive.nz/hls/{$camera}.m3u8";
$camPoster = "https://api.corolive.nz/{$camera}/snap.webp?rand=" . rand();

require 'player.php';
?>

<script>
    var config = {
        source: '<?php echo $camSrc; ?>',
        poster: '<?php echo $camPoster; ?>',
        watermark: "https://corolive.nz/img/watermark.webp",
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
            onReady: function () {
                var plugin = this.getPlugin('click_to_pause');
                plugin && plugin.disable();
            },
        },
    };

    var player = new window.Clappr.Player(config);
</script>