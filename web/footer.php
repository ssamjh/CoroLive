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

<br><br><br>

<div class="navbar-static-bottom">
    <footer class="bg-body text-center">

        <div class="container overflow-hidden">
            <div class="row gx-5">
                <div class="col">
                    <div class="p-3 bg-body float-lg-start">
                        <h5 class="text-center fw-light">Locations & network sponsored by:</h5>
                        <a href="https://cfm.co.nz/" target="_blank">
                            <img src="img/footer-cfm.webp" class="image-center" draggable="false"
                                alt="CFM sponsor logo">
                        </a>
                    </div>
                </div>

                <div class="col">
                    <div class="p-3 bg-body float-lg-end">
                        <div class="card">
                            <div class="card-body">
                                <p class="card-text">To get in contact, email <kbd>hi@corolive.nz</kbd> or click
                                    the button below.</p>
                                <a href="mailto:hi@corolive.nz" class="btn btn-primary">Send email</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="p-3 bg-body float-lg-end">
                        <h5 class="text-center fw-light">Camera hardware sponsored by:</h5>
                        <a href="https://www.provision-isr.co.nz/" target="_blank">
                            <img src="img/footer-provision.webp" class="image-center" draggable="false"
                                alt="Provision-ISR sponsor logo">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center fw-light bg-dark text-white">
            &copy CoroLive
            <script>
                document.write(new Date().getFullYear());
            </script><br>Created with &#10084 using <a href="https://getbootstrap.com/">Bootstrap</a> on <a
                href="https://github.com/ssamjh/CoroLive">GitHub</a>
        </div>
    </footer>
</div>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-191965282-1"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'UA-191965282-1');
</script>

<script>
    var $buoop = {
        required: {
            e: -6,
            f: -6,
            o: -6,
            s: 12.2,
            c: -6
        },
        unsupported: true,
        api: 2022.03,
        noclose: true,
        reminder: 1
    };

    function $buo_f() {
        var e = document.createElement("script");
        e.src = "https://browser-update.org/update.min.js";
        document.body.appendChild(e);
    };
    try {
        document.addEventListener("DOMContentLoaded", $buo_f, false)
    } catch (e) {
        window.attachEvent("onload", $buo_f)
    }
</script>