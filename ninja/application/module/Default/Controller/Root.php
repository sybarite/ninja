<?php

namespace Controller;

/**
 * The Root controller is called when the home page of the website is requested.
 */
class Root extends \Ninja\Controller
{

    /**
     * @var \View\Layout\Standard
     */
    protected $_layout;

    public function _before()
    {
        $this->_layout = new \View\Layout\Standard();
    }

    /**
     * The homepage of the website
     */
    public function index()
    {
        $view = new \Ninja\View(array('default', 'markup/home.php'));

        $this->_layout->setContentSlot($view);
    }

    public function _after()
    {
        echo $this->_layout->render();
    }
}