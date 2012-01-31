<?php
// -- Bootstrap file ---------------------------------------------------------------------


// -- Environment setup ------------------------------------------------------------------

	// Set the default time zone
	date_default_timezone_set( 'Asia/Kolkata' );

	
// -- Autoloading Setup ------------------------------------------------------------------
	
	// Path to directory in which Zend library is located.
    define( 'ZEND_PATH', NINJA_VENDOR_PATH); // Comment if you don't use Zend or Zend dependencies

	if( defined('ZEND_PATH') )
	{
		Ninja::$autoLoader->registerNamespace('Zend', ZEND_PATH . DIRECTORY_SEPARATOR . 'Zend');
		set_include_path(get_include_path() . PATH_SEPARATOR . ZEND_PATH ); // Add Zend's parent directory to include path
	}

	// Register namespaces to autoload
	Ninja::$autoLoader->registerNamespace('Model', NINJA_APPLICATION_MODULE_DEFAULT_PATH . 'Model');  // You can chain to add additional namespaces

// -- Register any modules --

    // Send all requests under example.com/blog to modules/Blog/Controller
    Ninja::$router->registerModule('Blog', 'blog');

// -- Routing Setup [help: http://code.google.com/p/php-ninja/wiki/Routing] ---------------

    if( ! Ninja::$isCli ) // No routing during command line mode
    {
        // Note: the home controller no more needs to be registered. The 'Root' controller is called under the Default module for it.
        Ninja::$router->addRoute('foo', 'test/(:num)', 'routed/here/$1');
    }