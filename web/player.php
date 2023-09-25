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

<script src=" https://cdn.jsdelivr.net/npm/clappr@0.3.13/dist/clappr.min.js "></script>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-8">
            <div class="ratio ratio-16x9">
                <div id="player">
                </div>
            </div>
        </div>
    </div>
</div>