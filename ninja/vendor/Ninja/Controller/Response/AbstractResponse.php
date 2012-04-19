<?php
namespace Ninja\Controller\Response;

/**
 * Response class for a Request
 */
class AbstractResponse
{
    /**
     * Body content
     * @var array
     */
    protected $_body = array();

    /**
     * Array of headers. Each header is an array with keys 'name' and 'value'
     * @var array
     */
    protected $_headers = array();

    /**
     * Array of raw headers. Each header is a single string, the entire header to emit
     * @var array
     */
    protected $_headersRaw = array();

    /**
     * HTTP response code to use in headers
     * @var int
     */
    protected $_httpResponseCode = 200;

    /**
     * Flag; is this response a redirect?
     * @var boolean
     */
    protected $_isRedirect = false;


    /**
     * Normalize a header name
     * Normalizes a header name to X-Capitalized-Names
     *
     * @param $name
     * @return mixed|string
     */
    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(array('-', '_'), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }

    /**
     * Set a header
     *
     * If $replace is true, replaces any headers already defined with that
     * $name.
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     * @return AbstractResponse
     */
    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name  = $this->_normalizeHeader($name);
        $value = (string)$value;

        if ($replace)
        {
            foreach ($this->_headers as $key => $header)
            {
                if ($name == $header['name'])
                {
                    unset($this->_headers[$key]);
                }
            }
        }

        $this->_headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );

        return $this;
    }

    /**
     * Set redirect URL
     *
     * Sets Location header and response code. Forces replacement of any prior
     * redirects.
     *
     * @param string $url
     * @param int $code
     * @return AbstractResponse
     */
    public function setRedirect($url, $code = 302)
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
             ->setHttpResponseCode($code);

        return $this;
    }

    /**
     * Is this a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->_isRedirect;
    }

    /**
     * Return array of headers; see {@link $_headers} for format
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Clear headers
     *
     * @return AbstractResponse
     */
    public function clearHeaders()
    {
        $this->_headers = array();

        return $this;
    }

    /**
     * Clears the specified HTTP header
     *
     * @param  string $name
     * @return AbstractResponse
     */
    public function clearHeader($name)
    {
        if (! count($this->_headers))
        {
            return $this;
        }

        foreach ($this->_headers as $index => $header)
        {
            if ($name == $header['name'])
            {
                unset($this->_headers[$index]);
            }
        }

        return $this;
    }

    /**
     * Set raw HTTP header
     *
     * Allows setting non key => value headers, such as status codes
     *
     * @param string $value
     * @return AbstractResponse
     */
    public function setRawHeader($value)
    {
        $this->canSendHeaders(true);
        if ('Location' == substr($value, 0, 8))
        {
            $this->_isRedirect = true;
        }
        $this->_headersRaw[] = (string)$value;
        return $this;
    }


    /**
     * Retrieve all {@link setRawHeader() raw HTTP headers}
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->_headersRaw;
    }

    /**
     * Clear all {@link setRawHeader() raw HTTP headers}
     *
     * @return AbstractResponse
     */
    public function clearRawHeaders()
    {
        $this->_headersRaw = array();
        return $this;
    }

    /**
     * Clears the specified raw HTTP header
     *
     * @param  string $headerRaw
     * @return AbstractResponse
     */
    public function clearRawHeader($headerRaw)
    {
        if (! count($this->_headersRaw))
        {
            return $this;
        }

        $key = array_search($headerRaw, $this->_headersRaw);
        if ($key !== false)
        {
            unset($this->_headersRaw[$key]);
        }

        return $this;
    }

    /**
     * Clear all headers, normal and raw
     *
     * @return AbstractResponse
     */
    public function clearAllHeaders()
    {
        return $this->clearHeaders()
                    ->clearRawHeaders();
    }

    /**
     * Set HTTP response code to use with headers
     *
     * @param int $code
     * @return AbstractResponse
     * @throws Exception
     */
    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code))
        {
            throw new Exception('Invalid HTTP response code');
        }

        if ((300 <= $code) && (307 >= $code))
        {
            $this->_isRedirect = true;
        }
        else
        {
            $this->_isRedirect = false;
        }

        $this->_httpResponseCode = $code;
        return $this;
    }

    /**
     * Retrieve HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return $this->_httpResponseCode;
    }

    /**
     * Can we send headers?
     *
     * @param boolean $throw Whether or not to throw an exception if headers have been sent; defaults to false
     * @return boolean
     * @throws Exception
     */
    public function canSendHeaders($throw = false)
    {
        $ok = headers_sent($file, $line);
        if ($ok && $throw && $this->headersSentThrowsException)
        {
            throw new Exception('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }

    /**
     * Send all headers
     *
     * Sends any headers specified. If an {@link setHttpResponseCode() HTTP response code}
     * has been specified, it is sent with the first header.
     *
     * @return AbstractResponse
     */
    public function sendHeaders()
    {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headersRaw) || count($this->_headers) || (200 != $this->_httpResponseCode))
        {
            $this->canSendHeaders(true);
        }
        elseif (200 == $this->_httpResponseCode)
        {
            // Haven't changed the response code, and we have no headers
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->_headersRaw as $header)
        {
            if (!$httpCodeSent && $this->_httpResponseCode)
            {
                header($header, true, $this->_httpResponseCode);
                $httpCodeSent = true;
            }
            else
            {
                header($header);
            }
        }

        foreach ($this->_headers as $header)
        {
            if (!$httpCodeSent && $this->_httpResponseCode)
            {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            }
            else
            {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent)
        {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }

        return $this;
    }

    /**
     * Set body content
     *
     * If $name is not passed, or is not a string, resets the entire body and
     * sets the 'default' key to $content.
     *
     * If $name is a string, sets the named segment in the body array to
     * $content.
     *
     * @param string $content
     * @param null|string $name
     * @return AbstractResponse
     */
    public function setBody($content, $name = null)
    {
        if ((null === $name) || !is_string($name))
        {
            $this->_body = array('default' => (string) $content);
        }
        else
        {
            $this->_body[$name] = (string) $content;
        }

        return $this;
    }

    /**
     * Append content to the body content
     *
     * @param string $content
     * @param null|string $name
     * @return AbstractResponse
     */
    public function appendBody($content, $name = null)
    {
        if ((null === $name) || !is_string($name))
        {
            if (isset($this->_body['default']))
            {
                $this->_body['default'] .= (string) $content;
            }
            else
            {
                return $this->append('default', $content);
            }
        }
        elseif (isset($this->_body[$name]))
        {
            $this->_body[$name] .= (string) $content;
        }
        else
        {
            return $this->append($name, $content);
        }

        return $this;
    }

    /**
     * Clear body array
     *
     * With no arguments, clears the entire body array. Given a $name, clears
     * just that named segment; if no segment matching $name exists, returns
     * false to indicate an error.
     *
     * @param  string $name Named segment to clear
     * @return boolean
     */
    public function clearBody($name = null)
    {
        if (null !== $name)
        {
            $name = (string) $name;
            if (isset($this->_body[$name]))
            {
                unset($this->_body[$name]);
                return true;
            }
            return false;
        }

        $this->_body = array();
        return true;
    }

    /**
     * Return the body content
     *
     * If $spec is false, returns the concatenated values of the body content
     * array. If $spec is boolean true, returns the body content array. If
     * $spec is a string and matches a named segment, returns the contents of
     * that segment; otherwise, returns null.
     *
     * @param boolean $spec
     * @return string|array|null
     */
    public function getBody($spec = false)
    {
        if (false === $spec)
        {
            ob_start();
            $this->outputBody();
            return ob_get_clean();
        }
        elseif (true === $spec)
        {
            return $this->_body;
        }
        elseif (is_string($spec) && isset($this->_body[$spec]))
        {
            return $this->_body[$spec];
        }

        return null;
    }

    /**
     * Append a named body segment to the body content array
     *
     * If segment already exists, replaces with $content and places at end of
     * array.
     *
     * @param string $name
     * @param string $content
     * @throws Exception
     * @return AbstractResponse
     */
    public function append($name, $content)
    {
        if (!is_string($name))
        {
            throw new Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if (isset($this->_body[$name]))
        {
            unset($this->_body[$name]);
        }
        $this->_body[$name] = (string) $content;
        return $this;
    }

    /**
     * Prepend a named body segment to the body content array
     *
     * If segment already exists, replaces with $content and places at top of
     * array.
     *
     * @param string $name
     * @param string $content
     * @throws Exception
     * @return AbstractResponse
     */
    public function prepend($name, $content)
    {
        if (!is_string($name))
        {
            throw new Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if (isset($this->_body[$name]))
        {
            unset($this->_body[$name]);
        }

        $new = array($name => (string) $content);
        $this->_body = $new + $this->_body;

        return $this;
    }

    /**
     * Insert a named segment into the body content array
     *
     * @param  string $name
     * @param  string $content
     * @param  string $parent
     * @param  boolean $before Whether to insert the new segment before or
     * after the parent. Defaults to false (after)
     * @throws Exception
     * @return AbstractResponse
     */
    public function insert($name, $content, $parent = null, $before = false)
    {
        if (!is_string($name))
        {
            throw new Exception('Invalid body segment key ("' . gettype($name) . '")');
        }

        if ((null !== $parent) && !is_string($parent))
        {
            throw new Exception('Invalid body segment parent key ("' . gettype($parent) . '")');
        }

        if (isset($this->_body[$name]))
        {
            unset($this->_body[$name]);
        }

        if ((null === $parent) || !isset($this->_body[$parent]))
        {
            return $this->append($name, $content);
        }

        $ins  = array($name => (string) $content);
        $keys = array_keys($this->_body);
        $loc  = array_search($parent, $keys);
        if (!$before)
        {
            // Increment location if not inserting before
            ++$loc;
        }

        if (0 === $loc)
        {
            // If location of key is 0, we're prepending
            $this->_body = $ins + $this->_body;
        }
        elseif ($loc >= (count($this->_body)))
        {
            // If location of key is maximal, we're appending
            $this->_body = $this->_body + $ins;
        }
        else
        {
            // Otherwise, insert at location specified
            $pre  = array_slice($this->_body, 0, $loc);
            $post = array_slice($this->_body, $loc);
            $this->_body = $pre + $ins + $post;
        }

        return $this;
    }

    /**
     * Echo the body segments
     *
     * @return void
     */
    public function outputBody()
    {
        $body = implode('', $this->_body);
        echo $body;
    }

    /**
     * Send the response, including all headers, rendering exceptions if so
     * requested.
     *
     * @return void
     */
    public function sendResponse()
    {
        $this->sendHeaders();

//        if ($this->isException() && $this->renderExceptions()) {
//            $exceptions = '';
//            foreach ($this->getException() as $e) {
//                $exceptions .= $e->__toString() . "\n";
//            }
//            echo $exceptions;
//            return;
//        }

        $this->outputBody();
    }
}