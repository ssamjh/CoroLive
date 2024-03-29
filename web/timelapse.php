<?php
$backtrace = debug_backtrace();
$direct_call = true;

foreach ($backtrace as $trace) {
    if (basename($trace['file']) !== basename(__FILE__)) {
        $direct_call = false;
        break;
    }
}

if ($direct_call) {
    header('HTTP/1.0 403 Forbidden');
    echo 'You are not allowed to access this file directly.';
    exit;
}
?>

<?php

if (isset($_GET["date"])) {
    $date = DateTime::createFromFormat("Y-m-d", $_GET["date"]);

    if (DateTime::createFromFormat("Y-m-d", "2022-03-01") > $date) {
        $vidExt = "mp4";
        $imgExt = "jpg";
    } else {
        $vidExt = "webm";
        $imgExt = "webp";
    }

    $camSrc = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.{$vidExt}";
    $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.{$imgExt}";
} else {
    $date = new DateTime("now", new DateTimeZone("Pacific/Auckland"));

    $tlDate1 = DateTime::createFromFormat("h:i a", $date->format("h:i a"));
    $tlDate2 = DateTime::createFromFormat("h:i a", "0:00 am");
    $tlDate3 = DateTime::createFromFormat("h:i a", "6:05 am");

    if ($tlDate1 > $tlDate2 && $tlDate1 < $tlDate3) {
        $date->modify("-1 day");
        $camSrc = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.webp";
    } else {
        $camSrc = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.webp";
    }
}
?>

<head>
    <?php $cameraCapitalized = ucfirst($camera); ?>
    <?php $pageName = "$cameraCapitalized Timelapse - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <?php require 'og-image.php'; ?>

    <meta property="og:image" content="<?php echo "$camOgURL"; ?>" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">
        <?php echo "$cameraCapitalized"; ?> Timelapse
    </h3>

    <br>

    <?php require 'player.php'; ?>

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
                onReady: function () {
                    var plugin = this.getPlugin('click_to_pause');
                    plugin && plugin.disable();
                },
            },
        };

        var player = new window.Clappr.Player(config);
    </script>

    <?php
    $maxDate = new DateTime("now", new DateTimeZone('Pacific/Auckland'));

    $maxDate1 = DateTime::createFromFormat('h:i a', $maxDate->format('h:i a'));
    $maxDate2 = DateTime::createFromFormat('h:i a', '0:00 am');
    $maxDate3 = DateTime::createFromFormat('h:i a', '6:05 am');

    if ($maxDate1 > $maxDate2 && $maxDate1 < $maxDate3) {
        $maxDate->modify("-1 day");
    }
    ?>

    <br>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <form class="text-center">
                    <label class="text-center">
                        Or... pick another date:
                        <input type="date" class="form-control" name="date" min="<?php echo "$startDate"; ?>"
                            max="<?php echo $maxDate->format('Y-m-d'); ?>" value="<?php echo $date->format('Y-m-d'); ?>"
                            required>
                        <span class="validity"></span>
                    </label>

                    <p>
                        <button class="btn btn-secondary text-center">Submit</button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php require 'footer.php'; ?>
</body>

</html>