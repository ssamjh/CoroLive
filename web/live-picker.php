<br>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-8">
            <form class="text-center">
                <label class="text-center">
                    Stream quality:
                    <select name="select-quality" class="form-control" id="select-quality" onchange="qualityUpdate(this.value);cookieSet('quality', this.value, 365);">
                    <option value="auto">Auto</option>
                    <option value="high">High</option>
                    <option value="low">Low</option>
                    </select>
                </label>
            </form>
        </div>
    </div>
</div>

<script>
        // Function I got from the internet to save cookies
        function cookieSet(cname, cvalue, exdays) {
            const d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            let expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        // Function I got from the internet to retrieve cookies
        function cookieGet(cname) {
            let name = cname + "=";
            let ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }
        window.addEventListener('DOMContentLoaded', (event) => {
            // Hide the stop button
            var qualityCookie = cookieGet('quality');
            // Check if quality cookie exists
            if (qualityCookie != "") {
                // If it does, get and update page to the quality from last time
                qualityUpdate(qualityCookie);
                document.getElementById("select-quality").value = (qualityCookie);
            } else {
                // Otherwise if cookie doesn't exist, set to default
                document.getElementById("select-quality").value = "auto";
            }
        });
</script>

<script>
    function qualityUpdate(qual) {
            if (qual == "auto") {
                player.configure({
                source: 'https://api.corolive.nz/hls/<?php echo "$camera"; ?>.m3u8',
                });
            } else if (qual == "high") {
                player.configure({
                source: 'https://api.corolive.nz/hls/<?php echo "$camera"; ?>_high.m3u8',
                });
            } else if (qual == "low") {
                player.configure({
                source: 'https://api.corolive.nz/hls/<?php echo "$camera"; ?>_low.m3u8',
                });
            }
        }
</script>