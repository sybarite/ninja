<?php
// -- Console Bootstrap file ---------------------------------------------------------------------


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