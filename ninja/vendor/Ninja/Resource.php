<?php
namespace Ninja;

/**
* A central manager of resources that are used throughout the app
*/
class Resource
{
    /**
     * Holds a singleton instance of this class
     * @var \Ninja\Resource|null
     */
    private static $_instance = null;

    /**
     * @var bool
     */
    private static $isSessionInitialized = false;
    
    /**
     * The initialise function to create the instance
     * @return void
     */
    protected static function init()
    {
        self::setInstance(new Resource());
    }
    
    /**
     * The function used to initially set the instance
     *
     * @param Resource $instance
     * @throws \Ninja\Exception
     * @return void
     */
    static function setInstance(Resource $instance)
    {
        if (self::$_instance !== null)
        {
            throw new \Ninja\Exception('\\Ninja\\Resource is already initialised');
        }
        self::$_instance = $instance;
    }
    
    /**
     * Obtain the instance if it exists, if not create it
     *
     * @return Resource
     */
    static function getInstance()
    {
        if (self::$_instance === null)
        {
            self::init();
        }
        return self::$_instance;
    }

    /**
     * Get the cache object
     *
     * @param string $templateKey
     * @throws Exception
     * @return \Zend_Cache_Core
     */
    public static function getCache($templateKey='default')
    {
        if( ! \Ninja\Registry::isRegistered('Zend_Cache_Manager') )
        {
            \Ninja\Registry::set('Zend_Cache_Manager', new \Zend_Cache_Manager);
        }
        
        /**
        * Zend's Cache Manager instance
        * 
        * @var $cacheManager \Zend_Cache_Manager
        */
        $cacheManager = \Ninja\Registry::get('Zend_Cache_Manager');

        // Is this cache template already registered?
        $cache = $cacheManager->getCache($templateKey);
        if ($cache !== null)
            return $cache;
    
        // Create new object from cache template
        if (!isset(\Ninja::$config['ninja']['resource']['cache']))
            throw new \Ninja\Exception("No 'ninja.resource.cache' defined in configuration.");
        
        if (!isset(\Ninja::$config['ninja']['resource']['cache'][$templateKey]) || ! is_array(\Ninja::$config['ninja']['resource']['cache'][$templateKey]))
            throw new \Ninja\Exception("No configuration defined for 'ninja.resource.cache.$templateKey'.");

        // Store cache template
        $cacheManager->setCacheTemplate($templateKey, \Ninja::$config['ninja']['resource']['cache'][$templateKey]);
        
        return $cacheManager->getCache($templateKey);
    }

    /**
     * Get the database object
     *
     * @param string $templateKey
     * @throws Exception
     * @return \Ninja\Db
     */
    public static function getDb($templateKey='default')
    {
        $registry = \Ninja\Registry::getInstance();
        $registry_key = 'Ninja_Db_Manager';

        $dbCollection = ( isset($registry[$registry_key]) ) ? $registry[$registry_key] : array();

        // Is this db template already in registry?
        if (isset($dbCollection[$templateKey]))
            return $dbCollection[$templateKey]; // Return existing object

        // Create new object from db template
        if (!isset(\Ninja::$config['ninja']['resource']['db']))
            throw new \Ninja\Exception("No 'ninja.resource.db' defined in configuration.");

        if (!isset(\Ninja::$config['ninja']['resource']['db'][$templateKey]) || ! is_array(\Ninja::$config['ninja']['resource']['db'][$templateKey]))
            throw new \Ninja\Exception("No configuration defined for 'ninja.resource.db.$templateKey'.");

        // Database resource configs are stored in ninja.resource.database
        $dbTemplateConfig = \Ninja::$config['ninja']['resource']['db'][$templateKey];
        //$adapter = ( isset($db_template_config['adapter']) ) ? $db_template_config['adapter'] : 'Ninja_Db';

        // Create instance of adapter created with settings passed
        $db = new \Ninja\Db($dbTemplateConfig);

        $dbCollection[$templateKey] = $db;

        // Save collection back in registry
        $registry[$registry_key] = $dbCollection;

        return $db;
    }


    /**
     * Provdes an instance of the Zend_Mail with configurations defined.
     * @throws Exception
     * @return \Zend_Mail
     */
    public static function getMail()
    {
        static $isMailInitialized = false;
        
        if ($isMailInitialized === true)
            return new \Zend_Mail();
        
        // Configuration initialized beyond this point
        $isMailInitialized = true;
        
        // Check if any configuration defined?
        if (!isset(\Ninja::$config['ninja']['resource']['mail']))
            return new \Zend_Mail();
            
        $mailConfig = \Ninja::$config['ninja']['resource']['mail'];

        // Set defaultFrom
        if (isset($mailConfig['defaultFrom']))
        {
            $defaultFrom = $mailConfig['defaultFrom'];
            
            if (!is_array($defaultFrom))
                throw new \Ninja\Exception("The configuration 'mail.defaultFrom' must be an array.");
            
            if (!isset($defaultFrom['email']))
                throw new \Ninja\Exception("The configuration 'mail.defaultFrom.email' must be defined.");
                
            \Zend_Mail::setDefaultFrom( $defaultFrom['email'],
                                        isset($defaultFrom['name']) ? $defaultFrom['name'] : NULL
                                     );
        }
        
        // Set defaultReplyTo
        if (isset($mailConfig['defaultReplyTo']))
        {
            $defaultReplyTo = $mailConfig['defaultReplyTo'];
            
            if (!is_array($defaultReplyTo))
                throw new \Ninja\Exception("The configuration 'mail.defaultReplyTo' must be an array.");
            
            if (!isset($defaultReplyTo['email']))
                throw new \Ninja\Exception("The configuration 'mail.defaultReplyTo.email' must be defined.");
                
            \Zend_Mail::setDefaultReplyTo( $defaultReplyTo['email'],
                                           isset($defaultReplyTo['name']) ? $defaultReplyTo['name'] : NULL
                                        );
        }
        
        // Set default transport
        if (isset($mailConfig['transport']))
        {
            $transportSettings = $mailConfig['transport'];
            
            if (!is_array($transportSettings))
                throw new \Ninja\Exception("The configuration 'mail.transport' must be an array.");
                
            if (!isset($transportSettings['type']))
                throw new \Ninja\Exception("The configuration 'mail.transport.type' must be defined.");
                
            $adapter   = 'Zend_Mail_Transport_' . ucfirst($transportSettings['type']);
            $transport = null;
            
            switch($adapter)
            {
                case 'Zend_Mail_Transport_Smtp':
                    if( ! isset($transportSettings['host']) )
                        throw new \Ninja\Exception("The configuration 'mail.transport.host' must be defined for Smtp delivery.");
                
                    $transport = new \Zend_Mail_Transport_Smtp($transportSettings['host'], $transportSettings);
                    break;
                
                case 'Zend_Mail_Transport_File':
                    
                    // If file protocol, create the default callback
                    if (!isset($transportSettings['callback']))
                    {
//                        $transportSettings['callback'] = 'ninja_resource_zend_mail_transport_file_callback';
//                        function ninja_resource_zend_mail_transport_file_callback($transport) {
//                            return $transport->recipients . '_' . $_SERVER['REQUEST_TIME'] . '_' . mt_rand() . '.log';
//                        };
                        $transportSettings['callback'] = function($transport) {
                            return $transport->recipients . '_' . $_SERVER['REQUEST_TIME'] . '_' . mt_rand() . '.log';
                        };
                    }
                    
                    if (!isset($transportSettings['path']) || empty($transportSettings['path']))
                    {
                        $mail_log_path = NINJA_LOGS_PATH . 'mail';
                        
                        if (!is_dir($mail_log_path))
                        {
                            // Create a mail log directory
                            mkdir($mail_log_path, 02777);

                            // Set permissions (must be manually set to fix umask issues)
                            chmod($mail_log_path, 02777);
                        }
                        
                        $transportSettings['path'] = $mail_log_path; // use this default path instead
                    }

                    $transport = new \Zend_Mail_Transport_File($transportSettings);
                    break;
                
                default:
                    $transport = new $adapter($transportSettings);
            }
            
            \Zend_Mail::setDefaultTransport($transport);
        }
        
        return new \Zend_Mail();
    }

    /**
     * Initializes a session.
     * Must be called before any output is sent.
     *
     * @throws Exception
     * @return
     */
    public static function startSession()
    {
        // If session already initialized?
        if (self::$isSessionInitialized === TRUE)
            return; // nothing to do here
            
        // Configuration initialized beyond this point
        self::$isSessionInitialized = TRUE;
        
        // Check if session configuration defined?
        if (!isset(\Ninja::$config['ninja']['resource']['session']) || ! is_array(\Ninja::$config['ninja']['resource']['session']))
            throw new \Ninja\Exception("No 'ninja.resource.session' defined in configuration.");
        
        $session_config = \Ninja::$config['ninja']['resource']['session'];
        $session_validators = isset($session_config['validators']) ? $session_config['validators'] : NULL;
        
        unset($session_config['validators']); // Unset the validator config as it's not really a Zend_Session config
        
        // Set session config
        \Zend_Session::setOptions($session_config);
        
        // Start the Zend Session
        \Zend_Session::start();
        
        // Register every validator
        foreach($session_validators as $validator)
        {
            \Zend_Session::registerValidator(new $validator());
        }
    }
    
    /**
    * Provides an instance of the Zend Session Namespace
    * 
    * @param string $namespace Programmatic name of the requested namespace
    * @param boolean $singleInstance Prevent creation of additional accessor instance objects for this namespace
    * @return \Zend_Session_Namespace
    * @throws \Zend_Session_Exception
    */
    public static function getSessionNamespace($namespace = 'default', $singleInstance = FALSE)
    {
        self::startSession();
        return new \Zend_Session_Namespace($namespace, $singleInstance);
    }
}