<?php
defined('NINJA_DOCROOT') or die('NINJA_DOCROOT not defined.');

/**
* The purpose of this file is to load the bare minimum files anhd DEFINE useful CONSTANTS.
*/

/**
* Start time of ninja-app
*/
define( 'NINJA_START_TIME', microtime(true) );

/**
* Path to the ninja directory
*/
define( 'NINJA_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR );

/**
* Path to the ninja/library directory
*/
define( 'NINJA_VENDOR_PATH', NINJA_PATH . 'vendor' . DIRECTORY_SEPARATOR  );

/**
* Path to the ninja/application directory
*/
define( 'NINJA_APPLICATION_PATH', NINJA_PATH . 'application' . DIRECTORY_SEPARATOR  );

/**
* Path to ninja's tmp/cache directory
*/
define( 'NINJA_TMP_PATH', NINJA_PATH . 'tmp' . DIRECTORY_SEPARATOR );

/**
* Path to ninja's log directory
*/
define( 'NINJA_LOGS_PATH', NINJA_TMP_PATH . 'logs' . DIRECTORY_SEPARATOR );


/**
* Path to the application's model directory
*/
//define( 'NINJA_APPLICATION_MODEL_PATH',  NINJA_APPLICATION_PATH . 'model' . DIRECTORY_SEPARATOR );

/**
 * Path to the modules directory under the application
 */
define( 'NINJA_APPLICATION_MODULE_PATH', NINJA_APPLICATION_PATH . 'module' . DIRECTORY_SEPARATOR );

/**
 * Path to the default application module
 */
define( 'NINJA_APPLICATION_MODULE_DEFAULT_PATH', NINJA_APPLICATION_MODULE_PATH . 'Default' . DIRECTORY_SEPARATOR );


/**
* Path to the application's view directory
*/
//define( 'NINJA_APPLICATION_VIEW_PATH',  NINJA_APPLICATION_PATH . 'view' . DIRECTORY_SEPARATOR );


/**
* Path to the application's controller directory
*/
//define( 'NINJA_APPLICATION_CONTROLLER_PATH',  NINJA_APPLICATION_PATH . 'controller' . DIRECTORY_SEPARATOR );


/**
* Path to the application's data directory
*/
define( 'NINJA_APPLICATION_DATA_PATH',  NINJA_APPLICATION_PATH . 'data' . DIRECTORY_SEPARATOR );


/**
* Path to the application's config directory
*/
define( 'NINJA_APPLICATION_CONFIG_PATH',  NINJA_APPLICATION_PATH . 'config' . DIRECTORY_SEPARATOR );

/**
* Path to the application's library directory
*/
//define( 'NINJA_APPLICATION_LIBRARY_PATH',  NINJA_APPLICATION_PATH . 'library' . DIRECTORY_SEPARATOR );

/**
* Path to the application's library directory
*/
define( 'NINJA_APPLICATION_COMMON_PATH',  NINJA_APPLICATION_PATH . 'common' . DIRECTORY_SEPARATOR );

/**
 * Load the three most necessary files which cannot be autoloaded.
 * 1. Config class (so config files can do an array merge)
 * 2. \Ninja\Autoloader (duh, it can't be autoloaded)
 * 3. Singleton \Ninja class (which loads the autoloader!)
 */
require_once NINJA_VENDOR_PATH . 'Ninja/Config.php';
require_once NINJA_VENDOR_PATH . 'Ninja/Autoloader.php';
require_once NINJA_VENDOR_PATH . 'Ninja/Core.php';

/**
 * Alias/Proxy class to access \Ninja\Core as simply \Ninja
 */
class Ninja extends \Ninja\Core {}