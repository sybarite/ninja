<?php
    /**
     * @var \View\Layout\Helper $helper
     */
    $helper->appendStylesheet('assets/default/css/home.css') // add a stylesheet to head
           ->queueScript('assets/default/js/home.js'); // add a script to footer
?>

<h1><img src="<?php $helper->assetUrl('common/img/logo_medium.gif') ?>" alt="my website" /></h1>

<h2>Hello World!</h2>

<h3>This home page uses the following:</h3>

<h4>Server Side Components:</h4>
<ul>
    <li><strong>Controller:</strong> \Controller\Root</li>
    <li><strong>Layout:</strong> \View\Layout\Standard + /View/Layout/standard.markup.php</li>
    <li><strong>View:</strong> /View/markup/home.php</li>
</ul>


<h4>Client Side Components:</h4>
<ul>
    <li><strong>JavaScript:</strong> /assets/default/js/global.js + /assets/default/js/home.js</li>
    <li><strong>CSS:</strong> /assets/default/css/global.css + /assets/default/css/home.css</li>
</ul>