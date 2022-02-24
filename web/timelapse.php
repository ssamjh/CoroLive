<?php

if (isset($_GET['date']) ) {

    $date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
    
    if (DateTime::createFromFormat('Y-m-d', '2022-02-28') > $date) {
        $vidExt = 'mp4';
        $imgExt = 'jpg';
    } else {
        $vidExt = 'webm';
        $imgExt = 'webp';
    }

    $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/animation.{$vidExt}";
    $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/snap-05:00.{$imgExt}";
} else {

    $date = new DateTime("now", new DateTimeZone('Pacific/Auckland'));

    $tlDate1 = DateTime::createFromFormat('h:i a', $date->format('h:i a'));
    $tlDate2 = DateTime::createFromFormat('h:i a', '0:00 am');
    $tlDate3 = DateTime::createFromFormat('h:i a', '6:00 am');

    if ($tlDate1 > $tlDate2 && $tlDate1 < $tlDate3 ) {
        $date->modify("-1 day");
        $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/snap-05:00.webp";

    } else {
        $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/snap-05:00.webp";
    }
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