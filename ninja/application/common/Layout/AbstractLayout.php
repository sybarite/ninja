<?php
namespace Layout;

/**
 * Abstract class for every Layout
 */
abstract class AbstractLayout extends \Ninja\View
{
    /**
     * List of added slots
     * @var array
     */
    protected $_addedSlots = array();

    /**
     * Rendered version of above slots (populated just before layout is rendered)
     * @var array
     */
    protected $_renderedSlots;

    /**
     * Sets the helper instance to be used for this layout
     *
     * @param \Layout\Helper $template
     * @return void
     */
    public function setHelper($template)
    {
        $this->helper = $template;
    }

    /**
     * Returns the layout helper.
     *
     * @return \Layout\Helper
     */
    public function getHelper()
    {
        return $this->helper;
    }


    /**
     * @param  $name
     * @param  \Ninja\View $view
     * @return void
     */
    public function setSlot($name, \Ninja\View $view)
    {

        if( ! isset($view->helper) && ! is_null($this->getHelper()) )
        {
            $view->helper = $this->getHelper(); // provide the content view file access to the template helper
        }

        // Provide the content view file access to the 'layout'
        $view->layout = $this;

        $this->_addedSlots[$name] = $view;
    }

    /**
     * Renders the layout
     *
     * @override
     * @param null $file
     * @return string
     */
    public function render($file = null)
    {
        // Make sure all the slots are rendered before the layout is rendered

        $this->_renderedSlots = array();
        foreach ($this->_addedSlots as $key => $val)
        {
            $this->_renderedSlots[$key] = $val->render();
        }

        $this->slots = $this->_renderedSlots;

        return parent::render($file);
    }

    public function slot($name)
    {
        echo $this->_renderedSlots[$name];
    }
}
