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

require 'player.php';

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