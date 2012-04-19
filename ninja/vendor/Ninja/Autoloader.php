<?php
namespace Ninja;

/**
* Autoloader stack and namespace autoloader
*/
class Autoloader
{
    /**
     * Holds namespace to directory map registered using \Ninja::$autoloader->registerNamespace
     *
     * @var array
     */
	private $_registeredNamespaces = array();

    /**
     * Registers instance with spl_autoload stack
     */
    public function __construct()
    {
        spl_autoload_register(array($this, '_autoload'));
    }

    /**
     * Register a namespace and map it to a directory from which it should be autoloaded
     * @param string $namespace
     * @param string $path
     * @return Autoloader
     */
    public function registerNamespace($namespace, $path)
    {
        // remove any trailing '/' in path & register the namespace
        $this->_registeredNamespaces[$namespace] = rtrim($path, DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
    * Provides autoloading support for classes that follow Ninja's naming conventions.
    * @param string $className
    */
    protected function _autoload($className)
    {
        $expectedFile = $this->find($className);

        if( $expectedFile !== NULL )
        {
            // if file does not exist, then PHP itself will throw the fatal error
            require_once $expectedFile;
        }
    }

    /**
     * Predicts where a class name should be located depending
     * on the autoloader's registered namespaces.
     * 
     * @param string $className Class Name
     * @return null|string
     */
    public function find($className)
    {
        // Autodiscover the path from the class name
        // Implementation is PHP namespace-aware, and based on
        // Framework Interop Group reference implementation:
        // https://gist.github.com/1234504

        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\'))
        {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        $dirs = explode(DIRECTORY_SEPARATOR, $fileName);

        // check if namespace registered?
        if (count($dirs) > 1 && isset( $this->_registeredNamespaces[$dirs[0]]))
        {
            // let's make the final absolute path and return it
            return $this->_registeredNamespaces[$dirs[0]] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice( $dirs, 1 ) );
        }
        return null;
    }
}