<?php
// Restreamer memfs stream IDs per camera.
// Live stream: https://restreamer.corolive.nz/memfs/{id}.m3u8
$streamIds = [
    'whitianga' => '283795aa-816e-4d95-8ac0-05cabb67c05e',
    'whangamata' => 'd456ab62-df95-4872-a96a-734ff455996e',
    'thames' => 'afb4677d-4dc4-44e9-94dd-de5fb0b68c29',
];

function streamM3u8($camera)
{
    global $streamIds;
    return "https://restreamer.corolive.nz/memfs/{$streamIds[$camera]}.m3u8";
}
