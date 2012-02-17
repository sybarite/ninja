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

	// Register namespaces for Autoloading
	Ninja::$autoLoader->registerNamespace('Model', NINJA_APPLICATION_MODULE_DEFAULT_PATH . 'Model')
                      ->registerNamespace('View', NINJA_APPLICATION_MODULE_DEFAULT_PATH . 'View')
                      ->registerNamespace('Layout', NINJA_APPLICATION_COMMON_PATH . 'Layout');  // You can chain to add additional namespaces

// -- Register any modules --

    // Ninja::$router->registerModule('Blog', 'blog'); // will send all requests under example.com/blog to modules/Blog/Controller

// -- Routing Setup : Tutorial @ https://github.com/epicwhale/ninja/wiki/URI-Routing

    if( ! Ninja::$isCli ) // No routing during command line mode (since the router is not available)
    {
        /**
         * Routing in Ninja follows the same functionality as http://codeigniter.com/user_guide/general/routing.html
         *
         * e.g: if you need to route http://example.com/product/52 to http://example.com/catalog/product_lookup_by_id/52
         *      Ninja::$router->addRoute('product_lookup_by_id', 'product/:num', 'catalog/product_lookup_by_id/$1');
         *                                ^^ any unique name      ^^ source       ^^ destination
         */
    }