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