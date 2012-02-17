<?php
namespace Ninja;

/**
* View Wrapper. Assign variables for use in view files and render them.
*/
class View
{
    // Inspired and Credit: Kohana Team

    // Array of global variables
    protected static $_global_data = array();
    
    // View filename
    protected $_file;

    // Array of local variables
    protected $_data = array();

    /**
     * Sets the initial view filename and local data.
     *      $view = new View($file);
     * 
     * @param string|array|null $file view filename
     * @param array|null $data array of values
     */
    public function __construct($file = null, array $data = null)
    {
        if ($file !== null)
        {
            $this->setFile($file);
        }

        if ($data !== null)
        {
            // Add the values to the current data
            $this->_data = $data + $this->_data;
        }
    }

    /**
     * Captures the output that is generated when a view is included.
     * The view data will be extracted to make local variables.
     * This method is static to prevent object scope resolution.
     *
     * @static
     * @throws Exception
     * @param $ninja_view_filename
     * @param array $ninja_view_data
     * @return string
     */
    protected static function capture($ninja_view_filename, array $ninja_view_data)
    {
        // Import the view variables to local namespace
        extract($ninja_view_data, EXTR_SKIP);

        if (self::$_global_data)
        {
            // Import the global view variables to local namespace
            extract(self::$_global_data, EXTR_SKIP);
        }

        // Capture the view output
        ob_start();

        try
        {
            // Load the view within the current scope
            include $ninja_view_filename;
        }
        catch (Exception $e)
        {
            // Delete the output buffer
            ob_end_clean();

            // Re-throw the exception
            $e2 = new \Ninja\View\Exception($e->getMessage(), $e->getCode());

            throw $e;
        }

        // Get the captured output and close the buffer
        return ob_get_clean();
    }

    /**
     * Sets a global variable, similar to [self::set], except that the variable will be accessible to all views.
     *
     *      \Ninja\View::setGlobal($name, $value);
     *
     * @static
     * @param string $key variable name or an array of variables
     * @param mixed|null $value
     * @return void
     */
    public static function setGlobal($key, $value = null)
    {
        if (is_array($key))
        {
            foreach ($key as $key2 => $value)
            {
                self::$_global_data[$key2] = $value;
            }
        }
        else
        {
            self::$_global_data[$key] = $value;
        }
    }

    /**
     * Assigns a global variable by reference, similar to [Ninja_View::bind], except
     * that the variable will be accessible to all views.
     *
     *     \Ninja\View::bindGlobal($key, $value);
     *
     * @param   string  variable name
     * @param   mixed   referenced variable
     * @return  void
     */
    public static function bindGlobal($key, & $value)
    {
        self::$_global_data[$key] =& $value;
    }

    /**
     * Magic method, searches for the given variable and returns its value.
     * Local variables will be returned before global variables.
     *
     *     $value = $view->foo;
     *
     * [!!] If the variable has not yet been set, an exception will be thrown.
     *
     * @param   string  variable name
     * @return  mixed
     * @throws  Ninja\View\Exception
     */
    public function & __get($key)
    {
        if (array_key_exists($key, $this->_data))
        {
            return $this->_data[$key];
        }
        elseif (array_key_exists($key, self::$_global_data))
        {
            return self::$_global_data[$key];
        }
        else
        {
            throw new \Ninja\View\Exception("View variable `$key` is not set.");
        }
    }

    /**
     * Magic method, calls [self::set] with the same parameters.
     *
     *     $view->foo = 'something';
     *
     * @param   string  variable name
     * @param   mixed   value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method, determines if a variable is set.
     *
     *     isset($view->foo);
     *
     * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
     *
     * @param   string  variable name
     * @return  boolean
     */
    public function __isset($key)
    {
        return (isset($this->_data[$key]) OR isset(self::$_global_data[$key]));
    }

    /**
     * Magic method, unsets a given variable.
     *
     *     unset($view->foo);
     *
     * @param   string  variable name
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->_data[$key], self::$_global_data[$key]);
    }

    /**
     * Magic method, returns the output of [self::render].
     *
     * @return  string
     * @uses    self::render
     */
    public function __toString()
    {
        try
        {
            return $this->render();
        }
        catch (Exception $e)
        {
            // Display the exception message
            throw $e;
        }
    }

    /**
     * Sets the view filename.
     * 
     * @param string|array $file
     * @return \Ninja\View
     */
    public function setFile($file)
    {
        $this->_file = self::find($file);

        if( ! $this->_file )
        {
            \Ninja::$errorReporter->add("Could not find view file <em>`" . print_r($file, true) . "`</em>.", 4)
                                  ->terminate();
        }

        return $this;
    }

    /**
     * Finds a view file.
     * e.g
     *   'foo.php'
     *   array('Blog', 'foo.php')
     *
     * @param string|array $file
     * @return string|false Path to view file or false if not found
     */
    public static function find($file)
    {
        $moduleName = \Ninja\Controller\Router::DEFAULT_MODULE_NAME;
        $viewFile = $file;

        if (is_array($file))
        {
            if (count($file) !== 2)
            {
                throw new Exception('Invalid file path configuration');
            }

            $moduleName = $file[0];
            $viewFile = $file[1];
        }

        $file = NINJA_APPLICATION_MODULE_PATH . $moduleName . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . $viewFile;

        if (is_file($file))
            return $file;
        return false;
    }

    /**
     * Assigns a variable by name. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This value can be accessed as $foo within the view
     *     $view->set('foo', 'my value');
     *
     * You can also use an array to set several values at once:
     *
     *     // Create the values $food and $beverage in the view
     *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
     *
     * @param   string   variable name or an array of variables
     * @param   mixed    value
     * @return  $this
     */
    public function set($key, $value = NULL)
    {
        if (is_array($key))
        {
            foreach ($key as $name => $value)
            {
                $this->_data[$name] = $value;
            }
        }
        else
        {
            $this->_data[$key] = $value;
        }

        return $this;
    }

    /**
     * Assigns a value by reference. The benefit of binding is that values can
     * be altered without re-setting them. It is also possible to bind variables
     * before they have values. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This reference can be accessed as $ref within the view
     *     $view->bind('ref', $bar);
     *
     * @param   string   variable name
     * @param   mixed    referenced variable
     * @return  $this
     */
    public function bind($key, & $value)
    {
        $this->_data[$key] =& $value;

        return $this;
    }

    /**
     * Renders the view object to a string. Global and local data are merged
     * and extracted to create local variables within the view file.
     *
     *     $output = $view->render();
     *
     * [!!] Global variables with the same key name as local variables will be
     * overwritten by the local variable.
     *
     * @param    string|null  $view filename
     * @return   string
     * @throws   \Ninja\View\Exception
     * @uses     self::capture
     */
    public function render($file = null)
    {
        if ($file !== null)
        {
            $this->setFile($file);
        }

        if (empty($this->_file))
        {
            throw new \Ninja\View\Exception('You must set the file to use within your view before rendering it.');
        }
        
        // Combine local and global data and capture the output
        return self::capture($this->_file, $this->_data);
    }

}
