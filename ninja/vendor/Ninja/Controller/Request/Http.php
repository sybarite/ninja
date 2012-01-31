<?php
namespace Ninja\Controller\Request;

/**
 * The Request class which parses the request
 * and instantiates the controller to call an action
 *
*/
class Http extends AbstractRequest
{
    /**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';


	/**
	* The URI of the http request
	* @var string
	*/
	protected $_uri;

	/**
	* Referring URL
	*
	* @var string|null
	*/
	protected $_referrer;

	/**
	* Client user agent
	*
	* @var string
	*/
	protected $_userAgent;


	public function __construct()
	{
        /**
         * No code here as of now.
         */
	}

    /**
     * Create an HTTP Request Object based on the request from the Web Server
     *
     * @static
     * @return Http
     */
    public static function createFromServerRequest()
    {
        $request = new Http();

        /*
         * # .htaccess file #
         * RewriteEngine on
         * RewriteBase /macaroni/
         * RewriteCond %{REQUEST_FILENAME} !-f
         * RewriteCond %{REQUEST_FILENAME} !-d
         * RewriteRule ^(.*)$ index.php/$1 [L,QSA]
         * #RewriteRule ^(.*)$ index.php?/$1 [L,QSA] #For Hosts like Dreamhost, etc with FastCGI
        */
        $nakedPath = strtolower(self::getPrettyUrl(false)); // lower case without any prefix or suffix '/'
        $request->setRequestUri(empty($nakedPath) ? '' : ($nakedPath . '/')); // store the URI with a sffix '/'
        
        if (isset($_SERVER['HTTP_REFERER']))
		{
			// There is a referrer for this request
            $request->setReferrer($_SERVER['HTTP_REFERER']);
		}

		if ( isset($_SERVER['HTTP_USER_AGENT']) )
		{
			// Set the client user agent
            $request->setUserAgent($_SERVER['HTTP_USER_AGENT']);
		}

        return $request;
    }

    public function getRequestUri()
    {
        return $this->_uri;
    }

    /**
     * @param string $uri
     * @return Http
     */
    public function setRequestUri($uri)
    {
        $this->_uri = $uri;
        return $this;
    }

	/**
	 * Use "pretty" URLs in PHP on any server.
	 * From: http://forrst.com/posts/Use_pretty_URLs_in_PHP_on_any_server-05a
	 *
	 * Get's the current "pretty" URI from the URL.  It will also correct the QUERY_STRING server var and the $_GET array.
	 * It supports all forms of mod_rewrite and the following forms of URL:
	 *
	 * http://example.com/index.php/foo (returns '/foo')
	 * http://example.com/index.php?/foo (returns '/foo')
	 * http://example.com/index.php/foo?baz=bar (returns '/foo')
	 * http://example.com/index.php?/foo?baz=bar (returns '/foo')
	 *
	 * Similarly using mod_rewrite to remove index.php:
	 * http://example.com/foo (returns '/foo')
	 * http://example.com/foo?baz=bar (returns '/foo')
	 *
	 * @author      Dan Horrigan <http://dhorrigan.com>
	 * @copyright   Dan Horrigan
	 * @license     MIT License <http://www.opensource.org/licenses/mit-license.php>
	 * @param   bool    $prefix_slash   whether to return the uri with a '/' in front
	 * @return  string  the uri
	 */
	private static function getPrettyUrl($prefix_slash = true)
	{
		if (isset($_SERVER['PATH_INFO']))
		{
		    $uri = $_SERVER['PATH_INFO'];
		}
		elseif (isset($_SERVER['REQUEST_URI']))
		{
		    $uri = $_SERVER['REQUEST_URI'];
		    if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)
		    {
		        $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		    }
		    elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0)
		    {
		        $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		    }

		    // This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		    // URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		    if (strncmp($uri, '?/', 2) === 0)
		    {
		        $uri = substr($uri, 2);
		    }
		    $parts = preg_split('#\?#i', $uri, 2);
		    $uri = $parts[0];
		    if (isset($parts[1]))
		    {
		        $_SERVER['QUERY_STRING'] = $parts[1];
		        parse_str($_SERVER['QUERY_STRING'], $_GET);
		    }
		    else
		    {
		        $_SERVER['QUERY_STRING'] = '';
		        $_GET = array();
		    }
		    $uri = parse_url($uri, PHP_URL_PATH);
		}
		else
		{
		    // Couldn't determine the URI, so just return false
		    return false;
		}

		// Do some final cleaning of the URI and return it
		return ($prefix_slash ? '/' : '').str_replace(array('//', '../'), '/', trim($uri, '/'));
	}



    /**
     * Set GET values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return Ninja_Request
     */
    public function setQuery($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            throw new Exception('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setQuery($key, $value);
            }
            return $this;
        }
        $_GET[(string) $spec] = $value;
        return $this;
    }

    /**
     * Retrieve a member of the $_GET superglobal
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * Set POST values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return Ninja_Request
     */
    public function setPost($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            throw new Exception('Invalid value passed to setPost(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setPost($key, $value);
            }
            return $this;
        }
        $_POST[(string) $spec] = $value;
        return $this;
    }

    /**
     * Retrieve a member of the $_POST superglobal
     *
     * If no $key is passed, returns the entire $_POST array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPost($key = null, $default = null)
    {
        if (null === $key) {
            return $_POST;
        }

        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

     /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Retrieve a member of the $_ENV superglobal
     *
     * If no $key is passed, returns the entire $_ENV array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getEnv($key = null, $default = null)
    {
        if (null === $key) {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }

    /**
     * Get the request method: GET, POST, PUT, DELETE, etc
     * @return string
     */
    public function getMethod()
    {
        return $this->getServer('REQUEST_METHOD');
    }

    /**
     * Was the request made by POST?
     *
     * @return bool
     */
    public function isPost()
    {
        if ($this->getMethod() === 'POST')
            return true;

        return false;
    }

    /**
     * Was the request made by GET?
     *
     * @return bool
     */
    public function isGet()
    {
        if ($this->getMethod() === 'GET')
            return true;

        return false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }

    /**
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name   = $this->getServer('SERVER_NAME');
        $port   = $this->getServer('SERVER_PORT');

        if(null === $name) {
            return '';
        }
        elseif (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }

    /**
     * Get the client's IP Address
     *
     * @param boolean $checkProxy
     * @return string
     */
    public function getClientIP($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } else if ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
    }

	/**
	 * Whether AJAX request
	 *
	 * @return  boolean
	 */
	public function isAjax()
	{
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        {
            // This request is an AJAX request
            return true;
        }
        return false;
	}


    // End Zend Adopted Functions --------------------------------------------------------------


    /**
     * Referring URL
     * @return string
     */
    public function getReferrer()
    {
        return $this->_referrer;
    }

    /**
     * @param $referrer
     * @return Http
     */
    public function setReferrer($referrer)
    {
        $this->_referrer = $referrer;
        return $this;
    }


    /**
     * Client user agent
     * @return string
     */
    public function getUserAgent()
    {
        return $this->_userAgent;
    }

    /**
     * @param $userAgent
     * @return Http
     */
    public function setUserAgent($userAgent)
    {
        $this->_userAgent = $userAgent;
        return $this;
    }

    // End Kohana Adopted Functions -------------------------------------------------------------
}

