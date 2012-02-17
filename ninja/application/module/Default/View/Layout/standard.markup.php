<?php
    /**
     * @var array $slots
     */

    /**
     * @var \View\Layout\Helper $helper
     */
?>
<!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
    <meta charset="utf-8">

    <title></title>
    <meta name="description" content="">

    <!-- Mobile viewport optimized: h5bp.com/viewport -->
    <meta name="viewport" content="width=device-width">

    <!-- Render stylesheets -->
    <?php $helper->renderStylesheets(); ?>

    <!-- All JavaScript at the bottom, except Modernizr  -->
    <script src="<?php $helper->assetUrl('common/lib/modernizr.js') ?>"></script>

    <?php $helper->renderHeadScripts() ?>
</head>
<body>
<!-- Prompt IE 6 users to get a better browser. -->
<!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a>.</p><![endif]-->

    <header>

    </header>

    <div role="main">
        <?php echo $slots['content'] // load the content slot here ?>
    </div>

    <footer>

    </footer>


    <!-- JavaScript at the bottom for fast page loading -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="<?php $helper->assetUrl('common/lib/jquery.js') ?>"><\/script>')</script>

    <?php $helper->renderQueuedScripts() ?>


    <!-- Asynchronous Google Analytics snippet. -->
    <script>
        var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
        (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
            g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
            s.parentNode.insertBefore(g,s)}(document,'script'));
    </script>
</body>
</html>