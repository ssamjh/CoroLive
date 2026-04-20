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

$maxDate = new DateTime("now", new DateTimeZone('Pacific/Auckland'));
if ($maxDate->format('H:i') < '06:05') {
    $maxDate->modify('-1 day');
}

// Parse date(s) — supports single YYYY-MM-DD or range YYYY-MM-DD_YYYY-MM-DD
$dates = [];
if (isset($_GET["date"])) {
    $rawDate = $_GET["date"];
    $parts = explode('_', $rawDate);
    $parts = array_slice($parts, 0, 2);

    if (count($parts) === 2) {
        // Date range — fill in every day between start and end (max 7)
        $startD = DateTime::createFromFormat("Y-m-d", trim($parts[0]), new DateTimeZone("Pacific/Auckland"));
        $endD   = DateTime::createFromFormat("Y-m-d", trim($parts[1]), new DateTimeZone("Pacific/Auckland"));
        if ($startD && $endD) {
            if ($startD > $endD) { [$startD, $endD] = [$endD, $startD]; }
            $cur = clone $startD;
            $dayCount = 0;
            while ($cur <= $endD && $dayCount < 7) {
                $dStr = $cur->format('Y-m-d');
                if ($dStr >= $startDate && $dStr <= $maxDate->format('Y-m-d')) {
                    $dates[] = clone $cur;
                }
                $cur->modify('+1 day');
                $dayCount++;
            }
        }
    } else {
        // Single date
        $d = DateTime::createFromFormat("Y-m-d", trim($parts[0]), new DateTimeZone("Pacific/Auckland"));
        if ($d) {
            $dStr = $d->format('Y-m-d');
            if ($dStr >= $startDate && $dStr <= $maxDate->format('Y-m-d')) {
                $dates[] = $d;
            }
        }
    }
}

if (empty($dates)) {
    if ($currentTime < $cutoffTime) {
        $dates = [new DateTime("1 day ago", new DateTimeZone("Pacific/Auckland"))];
    } else {
        $dates = [new DateTime("now", new DateTimeZone("Pacific/Auckland"))];
    }
}

$date = $dates[0];

// Build DB source list for JS
$dbSources = [];
foreach ($dates as $d) {
    $basePath = "https://api.corolive.nz/{$camera}/archive/{$d->format('Y/m/d')}";
    $dbSources[] = ['url' => "{$basePath}/images.db", 'date' => $d->format('Y-m-d')];
}

$firstBasePath = "https://api.corolive.nz/{$camera}/archive/{$dates[0]->format('Y/m/d')}";
$camPoster     = "{$firstBasePath}/thumbnail.avif";
$camOgURL      = $camPoster;

$cameraCapitalized = ucfirst($camera);
$pageName = "$cameraCapitalized Timelapse - CoroLive";

// Form state
$formDateStart = $dates[0]->format('Y-m-d');
$formDateEnd   = count($dates) > 1 ? end($dates)->format('Y-m-d') : $formDateStart;
$isRange       = count($dates) > 1;
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
        #tlTimeJump {
            width: 7.2rem;
            background-color: transparent;
            border-color: rgba(255,255,255,.4);
            color: #fff;
        }
        #tlTimeJump::-webkit-calendar-picker-indicator { filter: invert(1); opacity: .7; }
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
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <button id="tlPrev" class="btn btn-outline-light btn-sm" disabled title="Previous frame (←)"><i class="fa fa-fw fa-backward-step"></i></button>
                                    <button id="tlPlay" class="btn btn-outline-light btn-sm" disabled><i class="fa fa-fw fa-play"></i></button>
                                    <button id="tlNext" class="btn btn-outline-light btn-sm" disabled title="Next frame (→)"><i class="fa fa-fw fa-forward-step"></i></button>
                                    <span class="small text-white">Speed:</span>
                                    <select id="tlSpeed" class="form-select form-select-sm text-white" style="width:auto;background-color:transparent;border-color:rgba(255,255,255,.4);" disabled>
                                        <option value="0.25">0.25×</option>
                                        <option value="0.5">0.5×</option>
                                        <option value="1" selected>1×</option>
                                        <option value="2">2×</option>
                                        <option value="4">4×</option>
                                        <option value="8">8×</option>
                                    </select>
                                    <input id="tlTimeJump" type="time" step="120" class="form-control form-control-sm" disabled title="Jump to time">
                                    <button id="tlFullscreen" class="btn btn-outline-light btn-sm" disabled><i class="fa fa-fw fa-expand"></i></button>
                                </div>
                            </div>
                            <div id="tlProgress" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.45);">
                                <div style="width:60%;text-align:center;color:#fff;">
                                    <div id="tlProgressLabel" class="mb-2">Loading frames&hellip;</div>
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
        var DB_SOURCES = <?php echo json_encode($dbSources); ?>;
        var MULTI_DAY = DB_SOURCES.length > 1;
        var FPS = 12;

        var frameEl       = document.getElementById('tlFrame');
        var timeEl        = document.getElementById('tlTime');
        var scrubber      = document.getElementById('tlScrubber');
        var progressEl    = document.getElementById('tlProgress');
        var progressLabel = document.getElementById('tlProgressLabel');
        var bar           = document.getElementById('tlBar');
        var bytesEl       = document.getElementById('tlBytes');
        var playBtn       = document.getElementById('tlPlay');
        var prevBtn       = document.getElementById('tlPrev');
        var nextBtn       = document.getElementById('tlNext');
        var speedSel      = document.getElementById('tlSpeed');
        var fsBtn         = document.getElementById('tlFullscreen');
        var timeJumpEl    = document.getElementById('tlTimeJump');
        var container     = document.getElementById('tlContainer');
        var controls      = document.getElementById('tlControls');

        // Hide time jump for multi-day (per-day time selection doesn't make sense across days)
        if (MULTI_DAY) { timeJumpEl.style.display = 'none'; }

        var frames = [], current = 0, userScrubbing = false, playing = false, timer = null;

        function timeToMins(t) {
            var p = t.split(':');
            return parseInt(p[0]) * 60 + parseInt(p[1]);
        }

        function fmt12(ts) {
            var parts = ts.split(':');
            var h = parseInt(parts[0]), m = parseInt(parts[1]);
            return ((h % 12) || 12) + ':' + (m < 10 ? '0' : '') + m + ' ' + (h >= 12 ? 'PM' : 'AM');
        }

        function fmtFrameLabel(frame) {
            var t = fmt12(frame.ts);
            if (MULTI_DAY && frame.date) {
                var d = new Date(frame.date + 'T12:00:00');
                return d.toLocaleDateString('en-NZ', { month: 'short', day: 'numeric' }) + ' ' + t;
            }
            return t;
        }

        function showFrame(i) {
            frameEl.src = frames[i].url;
            timeEl.textContent = fmtFrameLabel(frames[i]);
            timeEl.style.display = '';
            scrubber.value = i;
            timeJumpEl.value = frames[i].ts.substring(0, 5);
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

        // Controls show/hide
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

        // Playback controls
        playBtn.addEventListener('click', function () { playing ? pause() : play(); });
        prevBtn.addEventListener('click', function () {
            if (!frames.length) return;
            current = Math.max(0, current - 1);
            showFrame(current);
        });
        nextBtn.addEventListener('click', function () {
            if (!frames.length) return;
            current = Math.min(frames.length - 1, current + 1);
            showFrame(current);
        });

        // Fullscreen
        fsBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) { container.requestFullscreen(); }
            else { document.exitFullscreen(); }
        });
        document.addEventListener('fullscreenchange', function () {
            fsBtn.innerHTML = document.fullscreenElement
                ? '<i class="fa fa-fw fa-compress"></i>'
                : '<i class="fa fa-fw fa-expand"></i>';
        });

        speedSel.addEventListener('change', function () {
            if (playing) { clearInterval(timer); timer = null; startTimer(); }
        });

        // Scrubber
        scrubber.addEventListener('mousedown',  function () { userScrubbing = true; });
        scrubber.addEventListener('touchstart', function () { userScrubbing = true; });
        scrubber.addEventListener('input',      function () { current = parseInt(this.value); showFrame(current); });
        scrubber.addEventListener('mouseup',    function () { userScrubbing = false; });
        scrubber.addEventListener('touchend',   function () { userScrubbing = false; });

        // Arrow key navigation (skip when focus is on a form element)
        document.addEventListener('keydown', function (e) {
            var tag = document.activeElement && document.activeElement.tagName;
            if (tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA') return;
            if (!frames.length) return;
            if (e.key === 'ArrowLeft') {
                current = Math.max(0, current - 1);
                showFrame(current);
                e.preventDefault();
            } else if (e.key === 'ArrowRight') {
                current = Math.min(frames.length - 1, current + 1);
                showFrame(current);
                e.preventDefault();
            }
        });

        // Time jump input
        timeJumpEl.addEventListener('change', function () {
            if (!frames.length || !this.value) return;
            pause();
            var target = this.value;
            var best = 0, bestDiff = Infinity;
            frames.forEach(function (f, i) {
                var diff = Math.abs(timeToMins(f.ts.substring(0, 5)) - timeToMins(target));
                if (diff < bestDiff) { bestDiff = diff; best = i; }
            });
            current = best;
            showFrame(current);
        });

        // Sequential DB loader
        function loadAllDbs(SQL) {
            var idx = 0;
            function next() {
                if (idx >= DB_SOURCES.length) { finishLoading(); return; }
                var source = DB_SOURCES[idx];
                var dayTag = DB_SOURCES.length > 1 ? 'Day ' + (idx + 1) + ' of ' + DB_SOURCES.length + ' \u2013 ' : '';
                progressLabel.textContent = DB_SOURCES.length > 1
                    ? 'Loading day ' + (idx + 1) + ' of ' + DB_SOURCES.length + '\u2026'
                    : 'Loading frames\u2026';
                bar.style.width = '0%';
                bytesEl.textContent = '';

                fetch(source.url)
                    .then(function (res) {
                        var total = parseInt(res.headers.get('content-length') || '0');
                        var reader = res.body.getReader(), chunks = [], received = 0;
                        function pump() {
                            return reader.read().then(function (r) {
                                if (r.done) return;
                                chunks.push(r.value);
                                received += r.value.length;
                                if (total) bar.style.width = Math.round(received / total * 100) + '%';
                                var fmt = function (b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB'; };
                                bytesEl.textContent = dayTag + (total ? fmt(received) + ' / ' + fmt(total) : fmt(received));
                                return pump();
                            });
                        }
                        return pump().then(function () {
                            var buf = new Uint8Array(received), offset = 0;
                            chunks.forEach(function (c) { buf.set(c, offset); offset += c.length; });
                            return buf;
                        });
                    })
                    .then(function (buf) {
                        var db = new SQL.Database(buf);
                        var result = db.exec('SELECT timestamp, ext, data FROM frames ORDER BY id');
                        db.close();
                        if (result.length > 0) {
                            var newFrames = result[0].values.map(function (r) {
                                var mime = r[1] === '.avif' ? 'image/avif' : 'image/webp';
                                return { ts: r[0], date: source.date, url: URL.createObjectURL(new Blob([r[2]], { type: mime })) };
                            });
                            frames = frames.concat(newFrames);
                        }
                        idx++;
                        next();
                    })
                    .catch(function (e) {
                        console.error('Failed to load day ' + (idx + 1) + ':', e);
                        idx++;
                        next();
                    });
            }
            next();
        }

        function finishLoading() {
            if (!frames.length) {
                progressEl.innerHTML = '<div class="text-white p-3">Failed to load timelapse.</div>';
                return;
            }
            scrubber.max = frames.length - 1;
            [scrubber, playBtn, prevBtn, nextBtn, speedSel, fsBtn, timeJumpEl].forEach(function (el) { el.disabled = false; });
            progressEl.style.transition = 'opacity .6s';
            progressEl.style.opacity = '0';
            setTimeout(function () { progressEl.style.display = 'none'; }, 600);
            showFrame(0);
            play();
        }

        initSqlJs({ locateFile: function (f) { return 'https://cdn.jsdelivr.net/npm/sql.js@1.14.1/dist/' + f; } })
            .then(function (SQL) { loadAllDbs(SQL); })
            .catch(function (e) {
                progressEl.innerHTML = '<div class="text-white p-3">Failed to load timelapse.</div>';
                console.error(e);
            });
    }());
    </script>

    <br>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <form id="dateForm" class="text-center" action="timelapse">
                    <input type="hidden" name="camera" value="<?php echo $camera; ?>">
                    <div class="d-flex flex-wrap gap-3 justify-content-center align-items-end mb-2">
                        <div>
                            <label class="form-label mb-1" id="dateStartLabel" for="dateStart"><?php echo $isRange ? 'Start date' : 'Date'; ?></label>
                            <input type="date" id="dateStart" class="form-control"
                                min="<?php echo $startDate; ?>"
                                max="<?php echo $maxDate->format('Y-m-d'); ?>"
                                value="<?php echo $formDateStart; ?>" required>
                        </div>
                        <div id="dateEndWrap"<?php echo $isRange ? '' : ' style="display:none"'; ?>>
                            <label class="form-label mb-1" for="dateEnd">End date</label>
                            <input type="date" id="dateEnd" class="form-control"
                                min="<?php echo $startDate; ?>"
                                max="<?php echo $maxDate->format('Y-m-d'); ?>"
                                value="<?php echo $formDateEnd; ?>">
                        </div>
                    </div>
                    <div class="form-check d-inline-block mb-2">
                        <input class="form-check-input" type="checkbox" id="rangeToggle"<?php echo $isRange ? ' checked' : ''; ?>>
                        <label class="form-check-label" for="rangeToggle">Date range</label>
                    </div>
                    <p>
                        <button type="submit" class="btn btn-secondary">Load</button>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var rangeToggle  = document.getElementById('rangeToggle');
        var dateEndWrap  = document.getElementById('dateEndWrap');
        var dateStartEl  = document.getElementById('dateStart');
        var dateEndEl    = document.getElementById('dateEnd');
        var form         = document.getElementById('dateForm');
        var dateStartLabel = document.getElementById('dateStartLabel');

        rangeToggle.addEventListener('change', function () {
            dateEndWrap.style.display = this.checked ? '' : 'none';
            dateStartLabel.textContent = this.checked ? 'Start date' : 'Date';
        });

        dateStartEl.addEventListener('change', function () {
            if (dateEndEl.value && dateEndEl.value < this.value) {
                dateEndEl.value = this.value;
            }
            dateEndEl.min = this.value;
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var start = dateStartEl.value;
            if (!start) return;
            var dateParam = start;
            if (rangeToggle.checked && dateEndEl.value && dateEndEl.value !== start) {
                dateParam = start + '_' + dateEndEl.value;
            }
            window.location.href = 'timelapse?camera=<?php echo urlencode($camera); ?>&date=' + dateParam;
        });
    }());
    </script>

    <?php require 'footer.php'; ?>
</body>

</html>
