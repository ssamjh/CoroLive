<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="HandheldFriendly" content="true">

<meta property="og:url" content="https://corolive.nz<?php echo $_SERVER['REQUEST_URI']; ?>" />
<script>
function getMeta(metaName) {
    const metas = document.getElementsByTagName('meta');

    for (let i = 0; i < metas.length; i++) {
        if (metas[i].getAttribute('name') === metaName) {
            return metas[i].getAttribute('content');
        }
    }
    return '';
}
console.log(getMeta('description'));
</script>

<script src="libs/jquery-3.6.0/jquery.min.js"></script>
<script src="libs/bootstrap-5.1.3/js/bootstrap.bundle.min.js"></script>
<link href="libs/bootswatch-darky-5.1.3/bootstrap.min.css" rel="stylesheet">

<link rel="icon" type="image/webp" href="img/corolive64.webp">