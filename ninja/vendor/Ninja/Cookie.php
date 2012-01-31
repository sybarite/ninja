<?php
namespace Ninja;

/**
* Cookie Helper.
* 
* @author Dayson Pais <dayson@epicwhale.org>
*/
class Cookie
{
    private static $_initialized = false;
    
    /**
    * Holds default/pre-configured options for cookies
    * 
    * @var array
    */
    private static $_options = array(
                                'expire'    => 0,     // Relative time before the cookie expires, 0 for session cookie.
                                'path'      => '/',   // Restrict the path that the cookie is available to
                                'domain'    => null,  // Restrict the domain that the cookie is available to
                                'secure'    => false, // Only transmit cookies over secure connections
                                'httponly'  => false, // Only transmit cookies over HTTP, disabling Javascript access
                                'salt'      => false, // Used to generate sha key that is prefixed to cookie
                            );
    
//    /**
//    *
//    * during bootstrap or other areas. This is to allow overriding of
//    * configurations loaded from the config file.
//    *
//    * @var array|null
//      private static $_overriddenKeys = null; // for future implementation
//    */


    /**
     * @static
     * @throws Exception
     * @return
     */
    private static function _init()
    {
        if (self::$_initialized === true)
            return;
            
        self::$_initialized = true;
                
        // Initialized beyond this point
        
        // If Ninja Framework being used, then let's try to fetch the system config
        if ( class_exists('Ninja') && !isset(\Ninja::$config['ninja']['resource']['cookie']) )
            return; // Nothing to do here, accept default values
        
        // Fetch values from system config
        $cookie_config = \Ninja::$config['ninja']['resource']['cookie'];
        
        // Is the cookie config an array?
        if ( ! is_array($cookie_config) )
        {
            throw new \Ninja\Exception('The configuration `ninja.resource.cookie` must be an array.');
        }
        
        foreach ($cookie_config as $key => $val)
        {
            if (isset(self::$_options[$key]))
            {
                self::$_options[$key] = $val;
            }
        }
    }
                            
    /**
     * Sets a cookie to be sent back to the browser. If no options passed, uses default or preconfigured options.
     * 
     * @static
     * @param string $name Name of the cookie to set
     * @param string $value Value of the cookie to set
     * @param string|int $expire A relative string(like '+5 minutes') or a unix timestamp
     * @param array $options Associative array of additional options. - path, domain, secure, httponly, salt.
     *   path     => Relative time before the cookie expires, 0 for session cookie.
     *   domain   => Restrict the path that the cookie is available to
     *   secure   => Only transmit cookies over secure connections
     *   httponly => Only transmit cookies over HTTP, disabling Javascript access
     *   salt     => If string: custom salt to be used
     *               If false : no salt protection
     *               If null  : system default(grab from config)
     *
     *
     *
     * @return bool
     */
    public static function set($name, $value, $expire=null, array $options = array())
    {
        self::_init();
        
        $expire     = (isset($expire) || $expire !== null) ? $expire : self::$_options['expire'];
        $path       = isset($options['path'])     ? $options['path']     : self::$_options['path'];
        $domain     = isset($options['domain'])   ? $options['domain']   : self::$_options['domain'];
        $secure     = isset($options['secure'])   ? $options['secure']   : self::$_options['secure'];
        $httponly   = isset($options['httponly']) ? $options['httponly'] : self::$_options['httponly'];
        
        // If salt is Not Set, True or Null, then use default configuration set. Else use the String salt passed.
        $salt = (isset($options['salt']) && $options['salt'] !== true && $options['salt'] !== null) ? $options['salt'] : self::$_options['salt'];
        
        // If Salt not a string (maybe an int or some garbage?), then do not salt!
        if( ! is_string($salt) )
        {
            $salt = false;
        }
        
        // If it must be salted, prepend the salt
        if ($salt)
        {
            $value = self::salt($name, $value, $salt) . '~' . $value;
        }
        
        // If expire relative string, convert it to timestamp
        if ($expire && ! is_numeric($expire))
        {
            $expire = strtotime($expire);
        }
        
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
    
    /**
    * Gets the value of a cookie
    * 
    * @param string $key cookie name
    * @param mixed|null $default default value to return
    * @param string|false|null $salt
    *   If string: custom salt to be used
    *   If false : no salt protection
    *   If null  : system default(grab from config)
    * @return mixed|null
    */
    public static function get($key, $default = null, $salt = null)
    {
        self::_init();
        
        if ( ! isset($_COOKIE[$key]))
        {
            // The cookie does not exist
            return $default;
        }
        
        // Get the cookie value
        $cookie = $_COOKIE[$key];
        
        $salt_check = false; // Flag whehter to check salt or not?
        
        
        // true and null do the same thing
        if( $salt === true )
        {
            $salt = null;
        }
        
        // If not a string or boolean, then set back to default
        if( ! is_string($salt) && $salt !== false )
        {
            $salt = self::$_options['salt']; // Get the salt configuration
        }
        
        // Whether salt is a string?
        if( is_string($salt) )
        {
            $salt_check = true;
        }
        
        // Whether to check for salt?
        if ($salt_check)
        {
            $split_at = strpos($cookie, '~');
            
            // Salt expected, but not found!
            if ($split_at === false)
            {
                self::delete($key); // Cookie manipulated in user space
                return $default;
            }
            
            // Seperate the salt and the value
            list ($hash, $value) = explode('~', $cookie, 2);
            
            if( $hash !== self::salt($key, $value, $salt) )
            {
                self::delete($key); // Cookie manipulated in user space
                return $default;
            }
            
            return $value;
        }
        else
            return $cookie;
    }
    
    /**
    * Deletes a cookie. (by setting it to null and expiring it)
    * 
    * @param string $name
    * @return bool
    */
    public static function delete($name)
    {
        // Remove the cookie
        unset($_COOKIE[$name]);

        // Nullify the cookie and make it expire
        return setcookie($name, null, -86400, self::$_options['path'], self::$_options['domain'], self::$_options['secure'], self::$_options['httponly']);
    }
    
    /**
     * Generates a salt string for a cookie based on the name and value.
     *      $salt = \Ninja\Cookie::salt('theme', 'red');
     *
     * @static
     * @param string $name name of cookie
     * @param string $value value of cookie
     * @param string $salt
     * @author  Kohana   Team
     * @return string
     */
    public static function salt($name, $value, $salt = null)
    {
        self::_init();
        
        if( empty($salt) )
        {
            $salt = ( isset(self::$_options['salt']) && is_string(self::$_options['salt']) ) ? self::$_options['salt'] : '';
        }

        // Determine the user agent
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';

        return sha1($agent.$name.$value.$salt);
    }
}
