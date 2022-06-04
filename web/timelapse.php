<?php

function isSafari()
{
    return preg_match(
        "/^((?!chrome|android).)*safari/i",
        $_SERVER["HTTP_USER_AGENT"]
    );
}

if (isset($_GET["date"])) {
    $date = DateTime::createFromFormat("Y-m-d", $_GET["date"]);

    if (DateTime::createFromFormat("Y-m-d", "2022-03-01") > $date) {
        $vidExt = "mp4";
        $imgExt = "jpg";
    } else {
        $vidExt = "webm";
        $imgExt = "webp";
        if (isSafari()) {
            echo '<script type="text/javascript">window.onload = function () { alert("Timelapse videos after 28-Feb-2022 are not supported on Safari.\n\nIf you are on MacOS try another browser.\n\nThere is no fix until offical support is added by Apple."); }</script>';
        }
    }

    $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.{$vidExt}";
    $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.{$imgExt}";
    
} else {
    $date = new DateTime("now", new DateTimeZone("Pacific/Auckland"));

    $tlDate1 = DateTime::createFromFormat("h:i a", $date->format("h:i a"));
    $tlDate2 = DateTime::createFromFormat("h:i a", "0:00 am");
    $tlDate3 = DateTime::createFromFormat("h:i a", "6:05 am");

    if ($tlDate1 > $tlDate2 && $tlDate1 < $tlDate3) {
        $date->modify("-1 day");
        $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.webp";
    } else {
        $camURL = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/animation.webm";
        $camPoster = "https://api.corolive.nz/{$camera}/archive/{$date->format("Y")}/{$date->format("M")}/{$date->format("d")}/snap-05:00.webp";
    }
}
?>

<script type="text/javascript">
        var media = {
            dataProvider: {
                source: [{
                    url: "<?php echo "$camURL"; ?>",
                    width: "100%",
                    height: "100%"
                }],
                splashImages: [{
                    url: "<?php echo "$camPoster"; ?>",
                    width: "100%",
                    height: "100%"
                }]
            }
        };
        var element = document.getElementById("player");
        window.bigsoda.player.create(element, media);
</script>