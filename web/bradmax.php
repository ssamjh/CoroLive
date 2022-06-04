<script type="text/javascript">
        var media = {
            dataProvider: {
                source: [{
                    url: "<?php echo "$camSrc"; ?>",
                    width: "100%",
                    height: "100%"
                }],
                splashImages: [{
                    url: "<?php echo "$camPoster"; ?>",
                    width: "100%",
                    height: "100%"
                }]
            }
        };
        var element = document.getElementById("player");
        window.bigsoda.player.create(element, media);
</script>