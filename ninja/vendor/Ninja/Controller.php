<?php
namespace Ninja;

/**
* Controller
*/
abstract class Controller
{
    /**
     * Request that created the controller
     * @var \Ninja\Controller\Request\Http
     */
    public $request;

    /**
     * @var \Ninja\Controller\Response\Http
     */
    public $response;

    public function __construct(\Ninja\Controller\Request\AbstractRequest $request, \Ninja\Controller\Response\AbstractResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
      * The "index" function is always loaded by default if the second segment of the URI is empty.
      */
    abstract function index();

    /**
     * Called just before the controller action
     * @return void
     */
    function _before()
    {

    }

    /**
     * Called just after the controller action
     * @return void
     */
    function _after()
    {

    }

    /**
     * Renders a View File from the view application/view directory.
     *
     * @deprecated
     * @param $file View file location within the view folder
     * @param null|array $data Associative array or object of variables the view file can use
     * @return \Ninja\View
     */
    protected function _renderView($file, $data = null)
    {
        ////// THIS IS A HIGHLY DEPRECATED FEATURE ////////////
        /**
        * Earlier the third parameter used to be the 'template' object sent to the view file.
        * This is deprecated but since migration can be a pain, it is now backwards compatible.
        */
        if( func_num_args() === 3 )
        {
            $data['template'] = func_get_arg(2);
        }
        ////////////////////////////////////////////////////////
        
        return new \Ninja\View($file, $data);
    }
}