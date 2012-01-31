<?php
namespace Ninja\Controller;

/**
 * 
 */
class Router
{
    const DEFAULT_MODULE_NAME = 'Default';
    const CONTROLLER_EXTENSION = '.php';

    private $_routes = array();

    /**
     * Key/Value store of modules registered
     *  key   => alias
     *  value => module
     * @var array
     */
    protected $_registeredModules = array();

    public function __construct()
    {
        // Autoload controllers under the Default module by default
        \Ninja::$autoLoader->registerNamespace('Controller', NINJA_APPLICATION_MODULE_DEFAULT_PATH . 'Controller');
    }

    /**
     * Register a module and map it to a path under the url
     * 
     * E.g ->registerModule('Blog', 'blog')
     *
     * @param $moduleName
     * @param $urlAlias
     * @return Router
     */
    public function registerModule($moduleName, $urlAlias)
	{
        // Add module name to registered modules
		$this->_registeredModules[$urlAlias] = $moduleName;

        // Register the module namespace
        \Ninja::$autoLoader->registerNamespace($moduleName, NINJA_APPLICATION_MODULE_PATH . $moduleName);


        return $this;
	}

    /**
	* Add a route wildcard
	*
	* @param string $name
	* @param string $source the URI to be matched
	* @param string $destination destination to be re-routed to
	* @return \Ninja\Controller\Router
	*/
	public function addRoute($name, $source, $destination )
	{
		$this->_routes[$source] = $destination;

		return $this;
	}

    /**
     * Matches any routes added against the URI to determine if the controller/method needs to be remapped.
     *
     * @param string $requestedPath
     * @return string|false Routed URI or False
     */
    protected function _parseRoute($requestedPath)
    {
    	/**
    	* This function has been adapted from _parse_routes() in ./system/libraries/Router.php of CodeIgniter
    	*/
		$routes = $this->_routes;

		// Do we even have any custom routing to deal with?
		if(count($routes) == 0)
			return FALSE;

		$uri = $requestedPath;

		if (isset($routes[$uri]))
		{
			$uri = $routes[$uri];
			return $uri;
		}
		else
		{
			// Loop through the route array looking for wild-cards
			foreach ($routes as $key => $val)
			{
				// Convert wild-cards to RegEx
				$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

				// Does the RegEx match?
				if (preg_match('#^'.$key.'$#', $uri))
				{
					// Do we have a back-reference?
					if (strpos($val, '$') !== false AND strpos($key, '(') !== false)
					{
						$val = preg_replace('#^'.$key.'$#', $val, $uri);
					}
					return $val;
				}
			}
		}
		return false;
    }

	public function processHttpRequest(\Ninja\Controller\Request\Http $request)
    {
        $requestedUrl = rtrim($request->getRequestUri(), '/'); // remove trailing '/'

        // Parse the present request. Apply routes, find controller, function, params, etc
        $uriRoute = $this->_parseRoute($requestedUrl);

        if( $uriRoute !== false )
        {
            $requestedUrl = $uriRoute;
            $request->setParam('uriRoute', $uriRoute); // store the routed URI in a request param
        }


        $explode = explode('/', $requestedUrl, 2);

        $moduleName = self::DEFAULT_MODULE_NAME;
        $modulePathRequested = $requestedUrl;

        // if module registered
        if (isset($this->_registeredModules[$explode[0]]))
        {
            $moduleName = $this->_registeredModules[$explode[0]];

            if (count($explode) === 2)
            {
                $modulePathRequested = $explode[1];
            }
            else
            {
                $modulePathRequested = '';
            }
        }

        $mappedController = $this->_findController($moduleName, $modulePathRequested);

        if ($mappedController)
        {
            $request->setModuleName($moduleName)
                    ->setControllerName($mappedController['fqn'])
                    ->setActionName($mappedController['action'])
                    ->setActionParams($mappedController['params']);
        }

        return array(
            'concernedModule' => $moduleName, // Return the module name so in case of error, the \Ninja\Core::createWebApplication(..) knows which module's error controller to call
        );
    }

    /**
     * Find the most matching controller for a path under a module.
     *
     * @param string $moduleName the registered module this request is meant for
     * @param string $moduleRequestedPath the path requested under this module
     * @return array|bool
     */
    private function _findController($moduleName, $moduleRequestedPath)
    {
        // if root of the module being called
        if ($moduleRequestedPath === '')
        {
            $moduleRequestedPath = 'root';
        }

        $curRequestedPath = $this->_ucPostSlash($moduleRequestedPath);

        while($curRequestedPath !== '')
        {
            $predictedFileFullPath = NINJA_APPLICATION_MODULE_PATH . $moduleName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $curRequestedPath . self::CONTROLLER_EXTENSION;

            // Check if file exists
            if ($this->_isFile($predictedFileFullPath))
            {
                $mappedControllerFile =  'Controller' . '/' . $curRequestedPath . self::CONTROLLER_EXTENSION;

                // Find the class name expected in this file
                $controllerFqn = '\\' . str_replace('/', '\\', substr($mappedControllerFile, 0, -strlen(self::CONTROLLER_EXTENSION)));

                if ($moduleName !== self::DEFAULT_MODULE_NAME)
                {
                    $controllerFqn = '\\' . $moduleName . $controllerFqn;
                }

                $mappedControllerFile = NINJA_APPLICATION_MODULE_PATH . $moduleName . '/' . $mappedControllerFile;

                $actionAndParams = substr($moduleRequestedPath, strlen(strtolower($curRequestedPath)) + 1 );

                if (!$actionAndParams)
                {
                    $action = 'index';
                    $params = array();
                }
                else
                {
                    $explode = explode('/', $actionAndParams, 2);
                    $eCount = count($explode);
                    $action = $explode[0];
                    $params = ($eCount === 2) ? explode('/', $explode[1]) : array();
                }

                return array(
                    'fqn' => $controllerFqn,
                    'file' => $mappedControllerFile,
                    //'actionAndParams' => $actionAndParams,
                    'action' => $action,
                    'params' => $params
                );
            }

            // Remove the part after the last slash '/' and try again
            $lastSlash = strrpos($curRequestedPath, '/');

            if ($lastSlash !== false)
            {
                $curRequestedPath = substr($curRequestedPath, 0, $lastSlash);
            }
            else
            {
                break;
            }
        }

        return false;
    }

    /**
     * Upper case the immediate character after every slash '/' found
     *    foo/apple/boo ==> foo/Apple/Boo
     *
     * @param $input
     * @return string
     */
    private function _ucPostSlash($input)
    {
        $l = strlen($input);
        for ( $i=0; $i<$l; $i++ )
        {
            if ($input[$i]==='/')
            {
                if ( ($i+1) < $l ) // if next character exists
                {
                    $input[$i + 1] = strtoupper($input[$i + 1]);
                }
            }
        }
        return ucfirst($input);
    }

    /**
     * Check if a controller file exists or not
     * Used so that we can implement caching for filestat calls in the future.
     *
     * @param $file
     * @return bool
     */
    private function _isFile($file)
    {
        return is_file($file); // cache this in the future
    }
}
