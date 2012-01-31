<?php
namespace Ninja\Controller\Request;

/**
 * 
 */
class AbstractRequest
{
    /**
     * Module Name
     * @var string
     */
    protected $_moduleName;

    /**
     * FQN of the Controller
     *
     * @var string
     */
    protected $_controllerName;

    /**
     * Name of the Action requested in the Controller
     *
     * @var string
     */
    protected $_actionName;

    /**
     * Parameters to be passed to Action
     *
     * @var array
     */
    protected $_actionParams = array();

    /**
     * Any additional Key/Value pair values to be passed in the request
     *
     * @var array
     */
    protected $_params = array();


    /**
     * @param $moduleName
     * @return AbstractRequest
     */
    public function setModuleName($moduleName)
    {
        $this->_moduleName = $moduleName;
        return $this;
    }


    /**
     * Name of the Module
     * @return string Module Name
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }
    

    /**
     * @param $controllerName
     * @return AbstractRequest
     */
    public function setControllerName($controllerName)
    {
        $this->_controllerName = $controllerName;
        return $this;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }


    /**
     * @param $actionName
     * @return AbstractRequest
     */
    public function setActionName($actionName)
    {
        $this->_actionName = $actionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * @param array $params
     * @return AbstractRequest
     */
    public function setActionParams(array $params)
    {
        $this->_actionParams = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getActionParams()
    {
        return $this->_actionParams;
    }

    /**
     * Retreive an action parameter by index
     *
     * @param int $index
     * @param null $default
     * @return null
     */
    public function getActionParam($index, $default = null)
    {
        return isset($this->_actionParams[$index]) ? $this->_actionParams[$index] : $default;
    }

    /**
     * @param $value
     * @return AbstractRequest
     */
    public function setActionParam($value)
    {
        $this->_actionParams[] = $value;
        return $this;
    }

    /**
     * Returns no. of parameters
     * 
     * @return int
     */
    public function getActionParamCount()
    {
        return count($this->_actionParams);
    }

    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * Retreive a request parameter by key
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function getParam($key, $default = null)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : $default;
    }
}