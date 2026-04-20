<!doctype html>
<html lang="en" data-bs-theme="auto">

<?php
$startDates = [
    'thames' => '2021-05-06',
    'whangamata' => '2021-03-17',
    'whitianga' => '2019-03-17',
];

$allowedCameras = ['whitianga', 'whangamata', 'thames'];
$camera = $_GET['camera'] ?? null;

if (!in_array($camera, $allowedCameras)) {
    header("Location: /");
    exit;
}

$startDate = $startDates[$camera] ?? null;

$currentTime = new DateTime("now", new DateTimeZone("Pacific/Auckland"));
$cutoffTime = DateTime::createFromFormat("H:i", "06:00", new DateTimeZone("Pacific/Auckland"));

if (isset($_GET["date"])) {
    $date = DateTime::createFromFormat("Y-m-d", $_GET["date"]);
} else {
    if ($currentTime < $cutoffTime) {
        $date = new DateTime("1 day ago", new DateTimeZone("Pacific/Auckland"));
    } else {
        $date = new DateTime("now", new DateTimeZone("Pacific/Auckland"));
    }
}

$basePath = "https://api.corolive.nz/{$camera}/archive/{$date->format('Y/m/d')}";
$camSrc = "{$basePath}/images.db";
$camPoster = "{$basePath}/thumbnail.avif";
$camOgURL = "{$basePath}/thumbnail.avif";

$cameraCapitalized = ucfirst($camera);
$pageName = "$cameraCapitalized Timelapse - CoroLive";
?>

<head>
    <?php require 'head.php'; ?>
    <meta property="og:image" content="<?php echo $camOgURL; ?>" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h3 class="text-center">
        <?php echo $cameraCapitalized; ?> Timelapse
    </h3>

    <br>

    <script src="https://cdn.jsdelivr.net/npm/sql.js@1.14.1/dist/sql-wasm.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        #tlContainer:fullscreen {
            background: #000;
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #tlContainer:fullscreen .ratio {
            position: relative !important;
            width: min(100vw, calc(100vh * 16 / 9));
            height: min(100vh, calc(100vw * 9 / 16));
            padding-top: 0 !important;
        }
        #tlContainer:fullscreen .ratio::before { display: none; }
        #tlControls { transition: opacity .4s; }
    </style>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <div id="tlContainer">
                    <div class="ratio ratio-16x9" style="background:#000;">
                        <div>
                            <img id="tlFrame" src="<?php echo $camPoster; ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                            <div id="tlTime" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;padding:4px 10px;border-radius:4px;font-size:1.1rem;pointer-events:none;display:none;"></div>
                            <div id="tlControls" style="position:absolute;bottom:0;left:0;right:0;padding:6px 10px 8px;background:rgba(0,0,0,.55);opacity:0;">
                                <input id="tlScrubber" type="range" class="form-range mb-1" value="0" min="0" step="1" disabled>
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <button id="tlPlay" class="btn btn-outline-light btn-sm" disabled><i class="fa fa-fw fa-play"></i></button>
                                    <span class="small text-white">Speed:</span>
                                    <select id="tlSpeed" class="form-select form-select-sm text-white" style="width:auto;background-color:transparent;border-color:rgba(255,255,255,.4);" disabled>
                                        <option value="0.25">0.25×</option>
                                        <option value="0.5">0.5×</option>
                                        <option value="1" selected>1×</option>
                                        <option value="2">2×</option>
                                        <option value="4">4×</option>
                                        <option value="8">8×</option>
                                    </select>
                                    <button id="tlFullscreen" class="btn btn-outline-light btn-sm" disabled><i class="fa fa-fw fa-expand"></i></button>
                                </div>
                            </div>
                            <div id="tlProgress" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);">
                                <div style="width:60%;text-align:center;color:#fff;">
                                    <div class="mb-2">Loading frames&hellip;</div>
                                    <div class="progress mb-1"><div id="tlBar" class="progress-bar" style="width:0%"></div></div>
                                    <div id="tlBytes" class="small"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var DB_URL = '<?php echo $camSrc; ?>';
        var FPS = 12;
        var frameEl = document.getElementById('tlFrame');
        var timeEl = document.getElementById('tlTime');
        var scrubber = document.getElementById('tlScrubber');
        var progressEl = document.getElementById('tlProgress');
        var bar = document.getElementById('tlBar');
        var bytesEl = document.getElementById('tlBytes');
        var playBtn = document.getElementById('tlPlay');
        var speedSel = document.getElementById('tlSpeed');
        var fsBtn = document.getElementById('tlFullscreen');
        var container = document.getElementById('tlContainer');
        var frames = [], current = 0, userScrubbing = false, playing = false, timer = null;

        function fmt12(ts) {
            var parts = ts.split(':');
            var h = parseInt(parts[0]), m = parseInt(parts[1]);
            return ((h % 12) || 12) + ':' + (m < 10 ? '0' : '') + m + ' ' + (h >= 12 ? 'PM' : 'AM');
        }

        function showFrame(i) {
            frameEl.src = frames[i].url;
            timeEl.textContent = fmt12(frames[i].ts);
            timeEl.style.display = '';
            scrubber.value = i;
        }

        function startTimer() {
            var interval = Math.round(1000 / (FPS * parseFloat(speedSel.value)));
            timer = setInterval(function () {
                if (!userScrubbing) { current = (current + 1) % frames.length; showFrame(current); }
            }, interval);
        }

        function play() {
            if (playing) return;
            playing = true;
            playBtn.innerHTML = '<i class="fa fa-fw fa-pause"></i>';
            startTimer();
        }

        function pause() {
            if (!playing) return;
            playing = false;
            playBtn.innerHTML = '<i class="fa fa-fw fa-play"></i>';
            clearInterval(timer);
            timer = null;
        }

        var controls = document.getElementById('tlControls');
        var hideTimer = null;
        function showControls() {
            controls.style.opacity = '1';
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () { controls.style.opacity = '0'; }, 3000);
        }
        container.addEventListener('mousemove', showControls);
        container.addEventListener('touchstart', showControls);
        controls.addEventListener('mouseenter', function () { clearTimeout(hideTimer); controls.style.opacity = '1'; });
        controls.addEventListener('mouseleave', function () {
            hideTimer = setTimeout(function () { controls.style.opacity = '0'; }, 3000);
        });

        playBtn.addEventListener('click', function () { playing ? pause() : play(); });
        fsBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) { container.requestFullscreen(); }
            else { document.exitFullscreen(); }
        });
        document.addEventListener('fullscreenchange', function () {
            fsBtn.innerHTML = document.fullscreenElement ? '<i class="fa fa-fw fa-compress"></i>' : '<i class="fa fa-fw fa-expand"></i>';
        });
        speedSel.addEventListener('change', function () {
            if (playing) { clearInterval(timer); timer = null; startTimer(); }
        });

        scrubber.addEventListener('mousedown', function () { userScrubbing = true; });
        scrubber.addEventListener('touchstart', function () { userScrubbing = true; });
        scrubber.addEventListener('input', function () { current = parseInt(this.value); showFrame(current); });
        scrubber.addEventListener('mouseup', function () { userScrubbing = false; });
        scrubber.addEventListener('touchend', function () { userScrubbing = false; });

        initSqlJs({ locateFile: function (f) { return 'https://cdn.jsdelivr.net/npm/sql.js@1.14.1/dist/' + f; } })
            .then(function (SQL) {
                return fetch(DB_URL).then(function (res) {
                    var total = parseInt(res.headers.get('content-length') || '0');
                    var reader = res.body.getReader(), chunks = [], received = 0;
                    function pump() {
                        return reader.read().then(function (r) {
                            if (r.done) return;
                            chunks.push(r.value);
                            received += r.value.length;
                            if (total) bar.style.width = Math.round(received / total * 100) + '%';
                            var fmt = function (b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB'; };
                            bytesEl.textContent = total ? fmt(received) + ' / ' + fmt(total) : fmt(received);
                            return pump();
                        });
                    }
                    return pump().then(function () {
                        var buf = new Uint8Array(received), offset = 0;
                        chunks.forEach(function (c) { buf.set(c, offset); offset += c.length; });
                        return buf;
                    });
                }).then(function (buf) {
                    var db = new SQL.Database(buf);
                    var rows = db.exec('SELECT timestamp, ext, data FROM frames ORDER BY id')[0];
                    db.close();
                    frames = rows.values.map(function (r) {
                        var mime = r[1] === '.avif' ? 'image/avif' : 'image/webp';
                        return { ts: r[0], url: URL.createObjectURL(new Blob([r[2]], { type: mime })) };
                    });
                    scrubber.max = frames.length - 1;
                    scrubber.disabled = false;
                    playBtn.disabled = false;
                    speedSel.disabled = false;
                    fsBtn.disabled = false;
                    progressEl.style.transition = 'opacity .6s';
                    progressEl.style.opacity = '0';
                    setTimeout(function () { progressEl.style.display = 'none'; }, 600);
                    showFrame(0);
                    play();
                });
            })
            .catch(function (e) {
                progressEl.innerHTML = '<div class="text-white p-3">Failed to load timelapse.</div>';
                console.error(e);
            });
    }());
    </script>

    <?php
    $maxDate = new DateTime("now", new DateTimeZone('Pacific/Auckland'));
    if ($maxDate->format('H:i') < '06:05') {
        $maxDate->modify('-1 day');
    }
    ?>

    <br>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <form class="text-center" action="timelapse" method="get">
                    <input type="hidden" name="camera" value="<?php echo $camera; ?>">
                    <label class="text-center">
                        Or... pick another date:
                        <input type="date" class="form-control" name="date" min="<?php echo "$startDate"; ?>"
                            max="<?php echo $maxDate->format('Y-m-d'); ?>" value="<?php echo $date->format('Y-m-d'); ?>"
                            required>
                        <span class="validity"></span>
                    </label>

                    <p>
                        <button class="btn btn-secondary text-center">Submit</button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php require 'footer.php'; ?>
</body>

</html>