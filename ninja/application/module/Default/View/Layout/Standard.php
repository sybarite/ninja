<?php

namespace View\Layout;

/**
 * A sample layout which is being used by the home page
 */
class Standard extends \Layout\AbstractLayout
{
    public function __construct()
    {
        /**
         * @var \View\Layout\Helper $helper
         */
        $helper = new Helper();

        /**
         * Set the helper for this layout. Once done, you can access the helper using $helper
         * in all the view/layout files which are used with this layout.
         */
        $this->setHelper($helper);

        $helper->queueScript('assets/default/js/global.js')
               ->appendStylesheet('assets/default/css/global.css');



//        $helper->appendStylesheet("assets//css/global.css")
//                ->appendStylesheet('http://fonts.googleapis.com/css?family=Ubuntu:400,700')
//                ->appendScript("static/lib/modernizr.js");

        parent::__construct(array('Default' , 'Layout/standard.markup.php'));
    }

    /**
     * Set the view file for the content area
     *
     * @param \Ninja\View $view
     * @return void
     */
    public function setContentSlot($view)
    {
        $this->setSlot('content', $view);
    }
}
