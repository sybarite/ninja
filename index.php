<?php

   /**
   * Ninja-Power-This-App!
   */
   
   // Add --> SetEnv APPLICATION_ENV "development"
   // in your virtualhost / .htaccess to define development mode
   
   // What environment do we want to run it on?
   defined('APPLICATION_ENV')  || define('APPLICATION_ENV', isset($_REQUEST['APPLICATION_ENV']) ? $_REQUEST['APPLICATION_ENV'] : (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
   
   // Full path to docroot of the website
   define('NINJA_DOCROOT', __DIR__ .DIRECTORY_SEPARATOR);
   require NINJA_DOCROOT . 'ninja/vendor/Ninja/includes/init.php'; // define required constants
     
   // Enable Debug if NOT in production mode
   defined('NINJA_DEBUG') or define('NINJA_DEBUG', (APPLICATION_ENV !== 'production') ? TRUE : FALSE );
   
   // Start Ninja with the environment's configuration file
   Ninja::createWebApplication( NINJA_APPLICATION_CONFIG_PATH . APPLICATION_ENV . '.php' );
   
   // We're done here.