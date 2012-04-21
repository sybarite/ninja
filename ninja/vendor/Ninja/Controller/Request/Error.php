<?php
namespace Ninja\Controller\Request;

/**
 * When an invalid request is made, an instance of this Request class is created
 */
class Error extends AbstractRequest
{
    /**
     * @var \Exception
     */
    protected $_exception;

    /**
     * @var \Ninja\Controller\Request\AbstractRequest
     */
    protected $_errorRequest;


    /**
     * @param $exception
     * @return Error
     */
    public function setException($exception)
    {
        $this->_exception = $exception;
        return $this;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->_exception;
    }


    /**
     * @param \Ninja\Controller\Request\AbstractRequest $request
     * @return Error
     */
    public function setErrorRequest(\Ninja\Controller\Request\AbstractRequest $request)
    {
        $this->_errorRequest = $request;
        return $this;
    }

    /**
     * @return \Ninja\Controller\Request\AbstractRequest|\Ninja\Controller\Request\Http
     */
    public function getErrorRequest()
    {
        return $this->_errorRequest;
    }
}
