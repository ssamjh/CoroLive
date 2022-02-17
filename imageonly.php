<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xxl-8">
            <div class="ratio ratio-16x9">
                <img id="image-only" src="https://api.corolive.nz/<?php echo "$camera"; ?>/snap.jpg">
            </div>
        </div>
    </div>
</div>


<script>
setInterval(function() {
    var myImageElement = document.getElementById('image-only');
    myImageElement.src = 'https://api.corolive.nz/<?php echo "$camera"; ?>/snap.jpg?rand=' + Math.random();
}, 15000);
</script>