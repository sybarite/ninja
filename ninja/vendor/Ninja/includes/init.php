<?php
defined('NINJA_DOCROOT') or die('NINJA_DOCROOT not defined.');

/**
 * The purpose of this file is to load the bare minimum files and DEFINE useful CONSTANTS.
 * For a list of all the constants and their meanings, visit https://github.com/epicwhale/ninja/wiki/Defined-constants
*/

/**
 * Time at which execution of the request started.
 */
define( 'NINJA_START_TIME', microtime(true) );

/**
 * The path to the 'ninja/' directory within your project.
 */
define( 'NINJA_PATH', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR );

/**
 * Path to the directory in which third-party libraries like Zend, Doctrine, etc are stored.
 */
define( 'NINJA_VENDOR_PATH', NINJA_PATH . 'vendor' . DIRECTORY_SEPARATOR  );

/**
 * Path to the ninja/application directory
 */
define( 'NINJA_APPLICATION_PATH', NINJA_PATH . 'application' . DIRECTORY_SEPARATOR  );

/**
 * Path to the ninja/tmp directory
 */
define( 'NINJA_TMP_PATH', NINJA_PATH . 'tmp' . DIRECTORY_SEPARATOR );

/**
 * Path to the ninja/tmp/logs directory
 */
define( 'NINJA_LOGS_PATH', NINJA_TMP_PATH . 'logs' . DIRECTORY_SEPARATOR );

/**
 * Path to the ninja/application/module directory
 */
define( 'NINJA_APPLICATION_MODULE_PATH', NINJA_APPLICATION_PATH . 'module' . DIRECTORY_SEPARATOR );

/**
 * Path to the ninja/application/module/Default directory
 */
define( 'NINJA_APPLICATION_MODULE_DEFAULT_PATH', NINJA_APPLICATION_MODULE_PATH . 'Default' . DIRECTORY_SEPARATOR );

/**
 * Path to the application's data directory.
 * Unsure about this as of now, so do not use it.
 *
 * @deprecated
*/
define( 'NINJA_APPLICATION_DATA_PATH',  NINJA_APPLICATION_PATH . 'data' . DIRECTORY_SEPARATOR );

/**
 * Path to the ninja/application/config directory
 */
define( 'NINJA_APPLICATION_CONFIG_PATH',  NINJA_APPLICATION_PATH . 'config' . DIRECTORY_SEPARATOR );

/**
* Path to the ninja/application/common directory
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