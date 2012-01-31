<?php
namespace Ninja;

/**
* Ninja exception class.
*/
class Exception extends \Exception
{

	
	
	private static $_error_levels = array(
					E_ERROR				=>	'Error',
					E_WARNING			=>	'Warning',
					E_PARSE				=>	'Parsing Error',
					E_NOTICE			=>	'Notice',
					E_CORE_ERROR		=>	'Core Error',
					E_CORE_WARNING		=>	'Core Warning',
					E_COMPILE_ERROR		=>	'Compile Error',
					E_COMPILE_WARNING	=>	'Compile Warning',
					E_USER_ERROR		=>	'User Error',
					E_USER_WARNING		=>	'User Warning',
					E_USER_NOTICE		=>	'User Notice',
					E_STRICT			=>	'Runtime Notice'
				);
	
	
	/**
	* Ninja's Own Exception Handler.
	* Jumps into action incase of any exception and:
	* 
	* 	1. ALWAYS Logs the exception with details in logs/php_error.log (you deserve to know all errors, you should never have an error!)
	*   2. Displays the Exception details. (if debug mode is ON)
	* 	3. Additionally displays the stack trace, etc. (if debug mode is ON + xdebug enabled)
    *
    * @static
    * @param \Exception $exception
    * @return
	*/
    public static function Handler(\Exception $exception)
    {
    	/**
    	* Log no matter whether in debug mode or not.
    	*/
    	
    	// these are our templates
		$traceline = "#%s %s(%s): %s(%s)";
		$traceline_0 = "#%s %s%s%s(%s)";

		// alter your trace as you please, here
		$trace = $exception->getTrace();
		foreach ($trace as $key => $stackPoint) {
		    
            if( ! isset($trace[$key]['args']) ) // If no args?
                continue;
            
            // I'm converting arguments to their type
		    // (prevents passwords from ever getting logged as anything other than 'string')
		    $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
		}

		// build your tracelines
		$result = array();
		foreach ($trace as $key => $stackPoint) {
		    $result[] = sprintf(
		        ($key === 0) ? $traceline_0 : $traceline,
		        $key,
		        isset($stackPoint['file']) ? $stackPoint['file'] : '',
		        isset($stackPoint['line']) ? $stackPoint['line'] : '',
		        $stackPoint['function'],
		        isset($stackPoint['args']) ? implode(', ', $stackPoint['args']) : ''
		    );
		}
		// trace always ends with {main}
		$result[] = '#' . ++$key . ' {main}';

		
    	$log_msg_template  = "Exception:\t:exception" . PHP_EOL;
    	$log_msg_template .= "Msg:\t\t:message" . PHP_EOL;
    	$log_msg_template .= "File:\t\t:filename" . PHP_EOL;
    	$log_msg_template .= "Line:\t\t:line" . PHP_EOL;
    	$log_msg_template .= "Trace:". PHP_EOL . "\t\t:trace" ;
    	
    	\Ninja::$log->add(\Ninja\Log::_PHP_EXCEPTION, $log_msg_template, array(
    														':exception'	=> get_class($exception),
    														':message'		=> $exception->getMessage(),
    														':filename'		=> $exception->getFile(),
    														':line'			=> $exception->getLine(),
    														':trace'		=> implode( PHP_EOL . "\t\t", $result)
    													));
    	\Ninja::$log->write(); //make sure the log write happens
    	
    	// Future: Check if command-line and then display a commandline compatible message
    	// Refer to Kohana_Exception::text(Exception $e) //Gets a single line of text representing the exception

        // If command-line?
        if( \Ninja::$isCli )
        {
            // Display stripped down one-line message and exit.
            echo "\n" . self::text($exception) . "\n";
            return;
        }
        
    	
    	if( \Ninja::$debug )
    	{ //if in debug mode, show the display message

    		if( isset($exception->xdebug_message) )
    		{ //if xdebug is enabled, let's use the xdebug formatted message
				
				echo "<br /><font size='1'><table class='xdebug-error' dir='ltr' border='1' cellspacing='0' cellpadding='1'>";
				echo sprintf(
					"<tr><th align='left' bgcolor='#f57900' colspan=\"5\"><span style='background-color: #cc0000; color: #fce94f; font-size: x-large;'>( ! )</span> Fatal error: Uncaught exception '%s' with message '%s' in %s on line <i>%s</i></th></tr>",
					get_class($exception),
				    $exception->getMessage(),
				    $exception->getFile(),
				    $exception->getLine()
				);
				
				echo $exception->xdebug_message;
				echo "</table></font>";
    		}
    		else
    		{ //let's print our own php like message
				$display_msg = sprintf(
				    "<b>PHP Fatal error:</b>  Uncaught exception '<b>%s</b>' with message '<b>%s</b>' in %s:%s\n<b>Stack trace:</b>\n%s\nthrown in <b>%s</b> on line <b>%s</b>",
				    get_class($exception),
				    $exception->getMessage(),
				    $exception->getFile(),
				    $exception->getLine(),
				    implode("\n", $result),
				    $exception->getFile(),
				    $exception->getLine()
				);

				echo "<pre>$display_msg</pre>";
    		}
    	}
    }
    
    /**
	 * Custom PHP Error Handler
	 *
	 * The main reason we use this is permit
	 * PHP errors to be logged in our own log files since we may
	 * not have access to server logs.
     * @static
     * @param $severity
     * @param $message
     * @param null $filepath
     * @param null $line
     * @return bool
	*/
    public static function ErrorHandler($severity, $message, $filepath = NULL, $line = NULL)
    {
    	/**
    	* The following error types cannot be handled with a user defined function:
    	* E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING,
    	* and most of E_STRICT raised in the file where set_error_handler() is called.
    	* 
    	* Log no matter whether in debug mode or not.
    	*/
    	
    	$severity = ( ! isset(self::$_error_levels[$severity])) ? $severity : self::$_error_levels[$severity];
		$log_msg = "Severity: $severity\nMessage: $message\nFilename: $filepath\nLine Number: $line";
		
		$log_msg_template  = "Severity:\t:severity" . PHP_EOL;
    	$log_msg_template .= "Msg:\t\t:message" . PHP_EOL;
    	$log_msg_template .= "File:\t\t:filename" . PHP_EOL;
    	$log_msg_template .= "Line:\t\t:line" . PHP_EOL;
    	
		\Ninja::$log->add(\Ninja\Log::_PHP_EXCEPTION, $log_msg_template, array(
    														':severity'		=> $severity,
    														':message'		=> $message,
    														':filename'		=> $filepath,
    														':line'			=> $line
    													));

		// if in CLI?
        if (\Ninja::$isCli)
        {
            // Print message
            echo "\n$severity: $message in $filepath on line $line\n";
        }
		else if( \Ninja::$debug ) // if in debug mode(but NOT CLI), show the html display message
    	{
    		// Display Format --> Notice: Undefined variable: b in D:\ninja\application\controller\Test.php on line 8
			echo "<br><b>$severity</b>: $message in <b>$filepath</b> on line <b>$line</b><br>";    	
		}

		return TRUE; //if true, php does not log this error in its own log file (avoids double logging)
    }

    /**
     * Get a single line of text representing the exception:
     * Error [ Code ]: Message ~ File [ Line ]
     *
     * @static
     * @param \Exception $e
     * @return string
     */
	public static function text(\Exception $e)
	{
		return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
			get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
	}
	
}
