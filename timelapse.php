<?php
if (isset($_GET['date']))
{   
    $date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
    
    if(DateTime::createFromFormat('Y-m-d', '2022-02-28') > $date) {
        $vidExt = 'mp4';
        $imgExt = 'jpg';
    } else {
        $vidExt = 'webm';
        $imgExt = 'webp';
    }

    $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/animation.{$vidExt}";
    $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/snap-12:00.{$imgExt}";
} else {
    $camURL = "https://api.corolive.nz/{$camera}/animation.webm";
    $camPoster = "https://api.corolive.nz/{$camera}/snap.webp";
}
?>

<script>
var config = {
    source: '<?php echo "$camURL"; ?>',
    poster: '<?php echo "$camPoster"; ?>',
    parentId: '#player',
    position: 'bottom-right',
    mute: true,
    autoPlay: true,
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