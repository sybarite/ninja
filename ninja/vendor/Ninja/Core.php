<?php
namespace Ninja;

/**
* Ninja Application
* The core class
*/
class Core
{
    /**
     * Whether command line request?
     * @var string
     */
	public static $isCli;


    /**
     * Run on windows?
     * @var string
     */
	public static $isWindows;
	
	
	/**
	* Whether Debug enabled or no
	* 
	* @var boolean
	*/
	public static $debug = FALSE;
	
	
	/**
	* Access to all loaded configuration
	* 
	* @var array
	*/
	public static $config = FALSE;
	
	/**
	* Base URL to the application
	* 
	* @var string
	*/
	public static $baseUrl = '/';
	
	/**
	* Full URL of the current request
	* 
	* @var string
	*/
	public static $currentUrl = '';
	
	
	/**
	* Instance of Ninja Router for handling routes
	* @var \Ninja\Controller\Router
	*/
	public static $router;

    /**
     * Instance of the Command Runner if in CLI mode
     * @var \Ninja\Console\Command\Runner
     */
    public static $commandRunner;
	
	/**
	* Instance of Ninja Autoloader for handling autoloading of classes
	* that follow the class naming convention
	* @var \Ninja\Autoloader
	*/
	public static $autoLoader;
	
	/**
	* Instance of \Ninja\Log
	* 
	* @var \Ninja\Log
	*/
	public static $log;
	
	
	/**
	* Instance of internal \Ninja\ErrorReporter.php
	* 
	* @var \Ninja\ErrorReporter
	*/
	public static $errorReporter;
	
	
	private static $_init;
	
	
	/**
	* Initializes the Ninja-App Environment
	* Common to both Web + Console Request
	*/
	private static function createApplication()
	{
				
		// Whether debug mode enabled or not?
		if( defined('NINJA_DEBUG') )
			self::$debug = NINJA_DEBUG;
		
		if( ! isset( self::$config['ninja']['name'] )  )
		{
			throw new \Ninja\Exception('The configuration ninja.name is not defined.');
		}
		
		// Report ALL POSSIBLE ERRORS. Compatible with future versions of PHP.
		error_reporting(-1);
		
		
		// Determine if we are running in a command line environment
    	self::$isCli = (PHP_SAPI === 'cli');
    	
    	// Determine if we are running in a Windows environment
    	self::$isWindows = (DIRECTORY_SEPARATOR === '\\');
		
		
		// Check if the most evil magic quotes are enabled.
		if ( (bool) get_magic_quotes_gpc() )
			throw new \Ninja\Exception("Magic Quotes Enabled. Ninja refuses to fight!");
		
		// Check for register globals status
		if ( ini_get('register_globals') )
	    {
	        throw new \Ninja\Exception("register_globals Enabled. Ninja refuses to fight!");
	    }
	    
	    if( self::$debug  )
	    {
			ini_set('display_errors', 'On');
		}
		else
		{
			ini_set('display_errors', 'Off');
			error_reporting(0);
		}
		
		self::$log = new \Ninja\Log();
		
//		/**
//         * Provide the debug function which is an easy to way to add messages to the debug log.
//         *
//         * @param $message
//         * @param array $values
//         */
//		function debug($message, array $values = NULL)
//		{
//			\Ninja::$log->add(\Ninja\Log::DEBUG, $message, $values );
//		}

		/******Assumption: Upto this line, there is no scope for error**************/
    
    
		/**
		* Define our custom error handler
		*/
		set_error_handler(array('Ninja\Exception', 'ErrorHandler'));
    
    	/**
    	* Define our custom exception handler
    	*/
    	set_exception_handler( array('Ninja\Exception', 'Handler') );
		
		self::$errorReporter = new \Ninja\ErrorReporter();

	}
	
	/**
     * Create a Ninja Web Application
     * @static
     * @param  $config_file
     * @return null
     */
	public static function createWebApplication( $config_file )
	{
		if( self::$_init )
		{
			// Do not execute more than once
			return;
		}
		self::$_init = TRUE;

        // First let's initialize the autoloader
		self::$autoLoader = new \Ninja\Autoloader();

        // Autoload the Ninja library from now on
        self::$autoLoader->registerNamespace('Ninja', NINJA_VENDOR_PATH . 'Ninja');

        // The config file may use the \Ninja\Config class, so autoloading should already be initialized by here
		self::$config = require $config_file;

		// Run common initialization		
		self::createApplication();

		// Initialize router
		self::$router = new \Ninja\Controller\Router();

        $request = \Ninja\Controller\Request\Http::createFromServerRequest();

        if( isset( $_SERVER['SCRIPT_NAME'] ) )
		{
			self::$baseUrl = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'] , '/') + 1 ); //find the full url to this application from server root
		}
		
		self::$currentUrl = self::$baseUrl . $request->getRequestUri();

        // load application/bootstrap.php here
		self::doBootStrap(NINJA_APPLICATION_PATH . 'bootstrap.php');

        $routerProcessData = self::$router->processHttpRequest($request);

        // Initialize dispatcher
        $dispatcher = new \Ninja\Controller\Dispatcher();
        // Initialize response object
        $response   = new \Ninja\Controller\Response\Http();

        // If in debug mode, simply try to dispatch with no failover error controller
        if (NINJA_DEBUG)
        {
            // Any exception thrown is handled by \Ninja\Exception::handler($e) which displays it on screen and logs it as well
            self::_dispatch($dispatcher, $request, $response);
            $response->sendResponse();
        }
        else
        {
            try
            {
                self::_dispatch($dispatcher, $request, $response);
                $response->sendResponse();
            }
            catch (\Exception $e)
            {
                ob_clean(); // Anything in the output buffer must be cleaned.

                $errorRequest = new \Ninja\Controller\Request\Error();
                $errorResponse = new \Ninja\Controller\Response\Http();

                $concernedModule = $routerProcessData['concernedModule'];
                $errorControllerName = '\\Controller\\Error';

                // If error is not from the default module
                if ($concernedModule !== \Ninja\Controller\Router::DEFAULT_MODULE_NAME)
                {
                    // prefix the module name to the controller and check if it exists
                    $moduleErrorControllerName = '\\' . $concernedModule . $errorControllerName;
                    if (is_file(\Ninja::$autoLoader->find($moduleErrorControllerName)))
                    {
                        $errorControllerName = $moduleErrorControllerName;
                    }
                    // if module does not have an error controller, then leave it to the default
                }

                
                $errorRequest->setException($e)
                             ->setErrorRequest($request)
                             ->setModuleName('Default')
                             ->setControllerName($errorControllerName)
                             ->setActionName('index');

                self::_dispatch($dispatcher, $errorRequest, $errorResponse);
                $errorResponse->sendResponse();
            }
        }
	}

    /**
     * Dispatches a request in an output buffered environment and appends any captured
     * output into the response body.
     *
     * @static
     * @param Controller\Dispatcher $dispatcher
     * @param Controller\Request\AbstractRequest $request
     * @param Controller\Response\Http $response
     * @return void
     */
    private static function _dispatch(\Ninja\Controller\Dispatcher $dispatcher, \Ninja\Controller\Request\AbstractRequest $request, \Ninja\Controller\Response\Http $response)
    {
        ob_start();
            $dispatcher->dispatch($request, $response);
        $dispatchOutput = ob_get_contents();
        ob_clean();

        $response->append('ob_from_controller', $dispatchOutput);
    }

    /**
     * Create a Ninja Console Application
     * @static
     * @throws \Ninja\Exception
     * @param  $config_file
     * @return null
     */
    public static function createConsoleApplication( $config_file )
    {
        if( self::$_init )
		{
			// Do not execute more than once
			return;
		}
		self::$_init = TRUE;

        // fix for fcgi (taken from framework/yiic.php)
        defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

		// First let's initialize the autoloader
		self::$autoLoader = new \Ninja\Autoloader();

        // Autoload the Ninja library from now on
        self::$autoLoader->registerNamespace('Ninja', NINJA_VENDOR_PATH . 'Ninja');


        // The config file may use the \Ninja\Config class, so autoloading should already be initialized by here
		self::$config = require $config_file;

        // Run common initialization
		self::createApplication();

        if( ! self::$isCli )
            throw new \Ninja\Console\Exception("Cannot create console application when not in command line.");

        // load application/bootstrap.php here
		self::doBootStrap(NINJA_APPLICATION_PATH . 'cli/bootstrap.php');

        // Register the 'Console' namespace at application/console
        self::$autoLoader->registerNamespace('Console', NINJA_APPLICATION_PATH . 'cli/Console');


        $args = $_SERVER['argv'];
        self::$commandRunner = new \Ninja\Console\Command\Runner($args[0]);

        array_shift($args);
        self::$commandRunner->run($args);

        
    }

    /**
     * Loads a file in an insolated space
     * @static
     * @param  $file file name
     * @return void
     */
	private static function doBootStrap($file)
	{
        // Load the bootstrap file, this allows routes, etc to be defined before the request is parsed and executed
		include $file;
	}
	
	
	/**
     * Access configuration array using a dot separator path.
     * For example \Ninja::$config['database']['default']['password'] can be accessed as:
     *       \Ninja::config('database.default.password');
     * 
     * @static
     * @param string $path the dot separated path to config
     * @param mixed|null $default a default value if path does not exist
     * @return mixed
     */
	public static function config($path, $default = NULL)
	{
		return \Ninja\Config::path(self::$config, $path, $default , '.');
	}
	
}
