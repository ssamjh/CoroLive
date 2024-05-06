<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php
$startDates = [
    'thames' => '2021-05-06',
    'whangamata' => '2021-03-17',
    'whitianga' => '2019-03-17',
];

$allowedCameras = ['whitianga', 'whangamata', 'thames'];
$camera = $_GET['camera'] ?? null;

if (!in_array($camera, $allowedCameras)) {
    header("Location: /");
    exit;
}

$startDate = $startDates[$camera] ?? null;

$currentTime = new DateTime("now", new DateTimeZone("Pacific/Auckland"));
$cutoffTime = DateTime::createFromFormat("H:i", "22:30", new DateTimeZone("Pacific/Auckland"));

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
    if ($currentTime < $cutoffTime) {
        $date = new DateTime("1 day ago", new DateTimeZone("Pacific/Auckland"));
    } else {
        $date = new DateTime("now", new DateTimeZone("Pacific/Auckland"));
    }

    $camSrc = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.webm";
    $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.webp";
}

$cameraCapitalized = ucfirst($camera);
$pageName = "$cameraCapitalized Timelapse - CoroLive";

require 'head.php';
require 'og-image.php';
?>

<head>
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
                <form class="text-center" action="timelapse" method="get">
                    <input type="hidden" name="camera" value="<?php echo $camera; ?>">
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