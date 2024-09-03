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
if (isset($_GET['date'])) {
    $dateOgCurrent = DateTime::createFromFormat('Y-m-d', $_GET['date']);
    $cutoff2022 = DateTime::createFromFormat('Y-m-d', '2022-02-28');
    $cutoff2024 = DateTime::createFromFormat('Y-m-d', '2024-09-01');

    if ($dateOgCurrent <= $cutoff2022) {
        $imgExt = 'jpg';
    } elseif ($dateOgCurrent < $cutoff2024) {
        $imgExt = 'webp';
    } else {
        $imgExt = 'avif';
    }

    $camOgURL = "https://api.corolive.nz/{$camera}/archive/{$dateOgCurrent->format('Y')}/{$dateOgCurrent->format('M')}/{$dateOgCurrent->format('d')}/snap-12:00.{$imgExt}";
} else {
    $camOgURL = "https://api.corolive.nz/{$camera}/snap.webp";
}