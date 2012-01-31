<?php

    /**
    * Ninja-Console-Power-!
    */

    // Full path to docroot of the website
    define('NINJA_DOCROOT', realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..')  . DIRECTORY_SEPARATOR );
    require NINJA_DOCROOT . 'ninja/vendor/Ninja/includes/init.php'; // define required constants

    // Enable Debug
    defined('NINJA_DEBUG') or define('NINJA_DEBUG', TRUE);

    // Start Ninja with the environment's configuration file
    Ninja::createConsoleApplication( NINJA_APPLICATION_CONFIG_PATH . 'console.php' );

    // We're done here.