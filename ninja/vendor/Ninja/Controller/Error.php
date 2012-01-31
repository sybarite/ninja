<?php
namespace Ninja\Controller;

/**
 * Controller class inherited by Error Controllers
 */
abstract class Error extends \Ninja\Controller
{
    /**
     * @var \Ninja\Controller\Request\Error
     */
    public $request;

    public function __construct(\Ninja\Controller\Request\AbstractRequest $request, \Ninja\Controller\Response\AbstractResponse $response)
    {
        parent::__construct($request, $response);

        // if this controller is called directly from the browser, then throw a 404 exception
        if (!is_a($request, 'Ninja\Controller\Request\Error'))
        {
            throw new \Ninja\Controller\Exception('Error controller called, so error will be thrown', 404);
        }

        // Send it to the exception handler for logging
        \Ninja\Exception::Handler($this->request->getException());
    }
}
