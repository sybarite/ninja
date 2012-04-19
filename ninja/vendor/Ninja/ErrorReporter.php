<?php
namespace Ninja;

/**
 * Handles the task of reporting framework (Routing + MVC) errors.
 */
class ErrorReporter
{
    /**
     * Framework related error + help messages
     *
     * @var array
     */
    protected $_helpTexts = array(
        9 => array(
            'type'  => 'The controller file included does not declare the expected Class',
            'soln'  => 'Make sure that the controller file name and the class declared within it are appropriate.'
        ),
        2 => array(
            'type'  => 'Action not accessible.',
            'soln'  => 'If a function in your Controller begins with an <em>underscore( _ )</em>, it cannot be accessed as an action.'
        ),
        3 => array(
            'type'  => 'Class is not a controller.',
            'soln'  => 'All controllers must extend the <em>Controller</em> class.'
        ),
        10 => array(
            'type'  => 'Requested Controller is Abstract.',
            'soln'  => 'Controllers with an an <em>Abstract</em> prefix are treated as Abstract classes and cannnot be instantiated.'
        ),
        7 => array(
            'type'  => 'Action declared as static function.',
            'soln'  => 'Actions defined in a controller must be <em>non-static</em>.'
        ),
        8 => array(
            'type'  => 'Action does not have a public visibility.',
            'soln'  => 'Actions defined in a controller must have a <em>public</em> visibility.'
        ),
        1 => array(
            'type'  => 'Not enough parameters.',
            'soln'  => 'The number of parameters passed to an action must be <em>more than or equal to</em> than the number of parameters in the functions signature.'
        ),
        0 => array(
            'type'  => 'Requested action not found.',
            'soln'  => 'Either define the action or create an `_remap($action, $params)` member function which will handle all un-matched actions for this controller'
        ),
        6 => array(
            'type'  => 'No controller to handle this request.',
            'soln'  => 'Create a controller that can handle this request.'
        ),
        4 => array(
            'type'  => 'Missing view file.',
            'soln'  => 'Create the corresponding view file.'
        ),
        52 => array(
            'type'  => 'DEPRECATED FUNCTION.',
            'soln'  => 'DO NOT USE THIS function as it will removed in a future version.'
        ),
    );

    /**
     * List of log messages
     *
     * @var array
     */
    protected $_errorMessages;

    /**
     * Adds a framework error to report.
     * If in debug mode, writes it immediately to the log file. Else queue it till exit.
     *
     * @param string $msg
     * @param bool|int $errorId
     * @return \Ninja\ErrorReporter
     */
    public function add($msg, $errorId = false)
    {
        if (\Ninja::$debug)
        {
            $error = $this->_helpTexts[$errorId];
            $error['msg'] = $msg;

            $logMessage  = "Ninja:\t:type" . PHP_EOL;
            $logMessage .= "Msg:\t:msg" . PHP_EOL;
            $logMessage .= "Soln:\t:soln" . PHP_EOL;

            \Ninja::$log->add(\Ninja\Log::_PHP_EXCEPTION, $logMessage, array(
                ':type' => $error['type'],
                ':msg' => strip_tags($error['msg']),
                ':soln' => strip_tags($error['soln']),
            ) );

            $this->_errorMessages[] = $error;
        }
        return $this;
    }

    /**
     * Terminate the application request and:
     *
     *     1. Render errors on screen if in debug mode
     *     2. Show the 503 or 404 page if not in debug mode
     *
     * @throws Controller\Request\Exception
     */
    public function terminate()
    {
        if (\Ninja::$debug) // if debug mode enabled
        {
            $data = '<ul>';
            foreach( $this->_errorMessages as $error)
            {
                $msg  = "<b>Type:</b> " . $error['type'];
                $msg .= "<br /><b>Msg:</b>&nbsp;" . $error['msg'];
                $msg .= "<br /><b>Soln:</b>&nbsp;" . $error['soln'];

                $data .= "<li> $msg </li>";
            }
            $data .= '</ul>';

            header("HTTP/1.0 404 Not Found");
            include __DIR__ . DIRECTORY_SEPARATOR . 'includes/view/errors.php';
            exit();
        }
        else
        {
            throw new \Ninja\Controller\Request\Exception("Sorry, Page Not Found", 404);
        }
    }
}