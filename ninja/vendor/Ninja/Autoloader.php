<?php
namespace Ninja;

/**
* Autoload classes related.
*/
class Autoloader
{

    /**
     * Holds namespace to directory map registered using \Ninja::$autoloader->registerNamespace
     *
     * @var array
     */
	private $_registeredNamespaces = array();
	
	public function __construct()
	{
		spl_autoload_register(array($this, 'autoload'));
	}
	
	/**
	* Register a namespace and map it to a directory within your project for autoloading.
	* 
	* @param string $namespace
	* @param string $path
	* @return \Ninja\Autoloader
	*/
	public function registerNamespace($namespace, $path)
	{
		//$absolutePath = realpath($path); // Resolve absolute path and remove trailing '/' (has a file stat call)
        $absolutePath = rtrim($path, DIRECTORY_SEPARATOR); // Resolve absolute path and remove trailing '/'

//        if( $absolutePath === FALSE )
//		{
//			throw new Autoloader\Exception("Could not register namespace `$namespace` with invalid path `$path`");
//		}
		
		$this->_registeredNamespaces[$namespace] = $absolutePath;
		
		return $this;
	}
	
	/**
	* Provides autoloading support for classes that follow Ninja's naming conventions.
	* 
	* @param string $className
	*/
	private function autoload($className)
	{
        $expectedFile = $this->find($className);

		if( $expectedFile !== NULL )
        {
            require $expectedFile;
        }

		// Not found, then let php throw the Fatal Error
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
        // http://groups.google.com/group/php-standards/web/psr-0-final-proposal

    	$className = ltrim($className, '\\');
	    $fileName  = '';
	    $namespace = '';
	    if ($lastNsPos = strripos($className, '\\')) {
	        $namespace = substr($className, 0, $lastNsPos);
	        $className = substr($className, $lastNsPos + 1);
	        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	    }
	    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

	    // if $className = Model_Panel_User, then $filename = 'Model' . DS . 'Panel' . DS . 'User.php' (e.g: Model\Panel\User.php)

	    $dirs = explode( DIRECTORY_SEPARATOR, $fileName );

	    // Check if namespace registered?
	    if ( count($dirs) > 1 && isset( $this->_registeredNamespaces[$dirs[0]] ) )
	    {
	    	// Yes, then let's make the final absolute path and return it
	    	return $this->_registeredNamespaces[$dirs[0]] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice( $dirs, 1 ) );
	    }
        return NULL;
    }
	
}