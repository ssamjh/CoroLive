<?php
function isMobileDevice()
{
    return preg_match("/mobile/i", $_SERVER["HTTP_USER_AGENT"]);
}
if (isMobileDevice()) {
    $camSrc = "https://api.corolive.nz/$camera/hls-low/live.stream.m3u8";
} else {
    $camSrc = "https://api.corolive.nz/$camera/hls/live.stream.m3u8";
}

$camPoster = "https://api.corolive.nz/{$camera}/snap.webp";

function isSafari()
{
    return preg_match("/iphone|ipad|ipod/i", $_SERVER["HTTP_USER_AGENT"]);
}
if (isSafari()) {
    require 'bradmax.php';
} else {
    require 'clapprjs.php';
}
?>



