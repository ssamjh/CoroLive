<?php
function isMobileDevice()
{
    return preg_match("/mobile/i", $_SERVER["HTTP_USER_AGENT"]);
}
if (isMobileDevice())
{
    $camSrc = "//api.corolive.nz/$_GET[camera]/hls-low/live.stream.m3u8";
}
else
{
    $camSrc = "//api.corolive.nz/$_GET[camera]/hls/live.stream.m3u8";
}
?>

<?php require 'player.php';?>

<script type="text/javascript">
        var media = {
            dataProvider: {
                source: [{
                    url: "<?php echo "$camSrc"; ?>",
                    width: "100%",
                    height: "100%"
                }],
                splashImages: [{
                    url: "//api.corolive.nz/<?php echo $_GET['camera']; ?>/snap.webp",
                    width: "100%",
                    height: "100%"
                }]
            }
        };
        var element = document.getElementById("player");
        window.bigsoda.player.create(element, media);
</script>