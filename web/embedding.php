<!doctype html>
<html lang="en">

<head>
    <?php $pageName = "Embedding - CoroLive"; ?>

    <?php require 'head.php'; ?>

    <meta property="og:image" content="img/corolive512.webp" />
</head>

<body>
    <?php require 'navbar.php'; ?>

    <h2 class="text-center">Embedding</h2>

    <br>

    <div class="text-center">
        <a>So you want to embed CoroLive on your own website?<br>Great, there's some details you should
            know before going ahead!</a>
        <br>
        <a>The easiest way to get started is to create an HTML iframe with the embed URL.</a>
        <br>
        <br>
        <a>Here's an example:</a>
        <br>
        <kbd>&lt;iframe width="640" height="360"
            src="https://corolive.nz/embed?camera=CAMERA-ID"&gt;&lt;/iframe&gt;</kbd>
        <br>
        <br>
        <a>Then simply replace <kbd>CAMERA-ID</kbd> with either <kbd>whitianga</kbd>,
            <kbd>whangamata</kbd>, or <kbd>thames</kbd>.</a>
        <br>
        <br>
        <div class="fw-lighter">You can learn more about the HTML iframe by <a class="fw-lighter"
                href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe">clicking here</a>.</div>
        <br>
        <br>
        <h5>Lastly, and most importantly...</h5>
        <a>...reach out and let me know you're going to embed CoroLive, this means I can keep track and whitelist
            your website. See below for contact details.</a>
    </div>

    <?php require 'footer.php'; ?>

</body>

</html>