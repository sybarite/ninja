<?php
    /**
     * @var \View\Layout\Helper $helper
     */
    $helper->setTitle('Home')
           ->setMetaDescription('This is my home page')
           ->appendStylesheet('assets/default/css/home.css') // add a stylesheet to head
           ->queueScript('assets/default/js/home.js'); // add a script to footer
?>

<!-- // excuse the inline css below. it's for representational purpose only. you can delete this entire block once you are ready! -->
<div style="background-color: #fff;margin: 40px;font: 13px/20px normal Helvetica, Arial, sans-serif;color: #4F5155">
    <div id="container" style="margin: 10px;border: 1px solid #D0D0D0;-webkit-box-shadow: 0 0 8px #D0D0D0">
        <h1 style="color: #444;background-color: transparent;border-bottom: 1px solid #D0D0D0;font-size: 19px;font-weight: normal;margin: 0 0 14px 0;padding: 14px 15px 10px 15px">Welcome to Ninja!</h1>

        <div id="body" style="margin: 0 15px 0 15px">
            <p>The page you are looking at is being generated dynamically by Ninja. View the source to see HTML5 Boilerplate awesomeness!</p>

            <p>If you are exploring Ninja for the very first time, you should start by reading the <a href="https://github.com/epicwhale/ninja/wiki/" style="color: #039;background-color: transparent;font-weight: normal">User Guide</a>.</p>

            <p>The corresponding <strong>Controller</strong> for this page is found at:</p>
            <code style="font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px">ninja/application/module/Default/Controller/Root.php</code>

            <p>If you would like to edit this page you'll find the <strong>View</strong> file located at:</p>
            <code style="font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px">ninja/application/module/Default/View/markup/home.php</code>

            <p>If you would like to edit this layout you'll find the <strong>Layout</strong> file located at:</p>
            <code style="font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px">ninja/application/module/Default/View/Layout/standard.markup.php</code>

            <p>The <strong>CSS</strong> files included are:</p>
            <code style="font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px">/assets/default/css/global.css <br />/assets/default/css/home.css</code>

            <p>The <strong>JavaScript</strong> files included are:</p>
            <code style="font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px">/assets/default/js/global.js <br /> /assets/default/js/home.js</code>

            <img src="<?php $helper->assetUrl('common/img/logo_medium.gif') ?>" alt="my website" />
        </div>

        <p class="footer" style="text-align: right;font-size: 11px;border-top: 1px solid #D0D0D0;line-height: 32px;padding: 0 10px 0 10px;margin: 20px 0 0 0">Page rendered in <strong><?php echo round(microtime(true) - NINJA_START_TIME, 4) ?></strong> sec / peak: <strong><?php echo memory_get_peak_usage(true) / 1024 / 1024 ?></strong> MB</p>
    </div>
</div>
<!-- // delete upto here -->