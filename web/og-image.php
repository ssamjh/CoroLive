<?php
if (isset($_GET['date']))
{   
    $date = DateTime::createFromFormat('Y-m-d', $_GET['date']);
    
    if(DateTime::createFromFormat('Y-m-d', '2022-02-28') > $date) {
        $imgExt = 'jpg';
    } else {
        $imgExt = 'webp';
    }

    $camOgURL = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y')}/{$date->format('M')}/{$date->format('d')}/snap-12:00.{$imgExt}";
} else {
    $camOgURL = "https://api.corolive.nz/{$camera}/snap.webp";
}?>