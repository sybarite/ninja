<?php
namespace Ninja\Controller;

/**
 * The request dispatcher
 */
class Dispatcher
{
    
    public function dispatch(\Ninja\Controller\Request\AbstractRequest $request, \Ninja\Controller\Response\AbstractResponse $response)
    {
        if (! $request->getControllerName())
        {//if no controller class found
            $error = "Controller not found for the request ";

            if (is_a($request, '\Ninja\Controller\Request\Http'))
            {
                $error .= "' {$request->getRequestUri()}' ";
            }

            if( $request->getParam('uriRoute') )
        	{
				$error .= " <em>which is routed to</em> `{$request->getParam('uriRoute')}`.";
        	}

        	\Ninja::$errorReporter->add( $error, 6)
            					 ->terminate();

            return;
        }


        // If a valid controller class found ?
        $controllerDirPath = NINJA_APPLICATION_MODULE_DEFAULT_PATH . 'Controller/'; // Directory under which all controllers are located

        $controllerFile =  \Ninja::$autoLoader->find($request->getControllerName());


        // Include the controller's class file
        require_once $controllerFile;


        // Check if expected controller class declared or not?
        if( ! class_exists($request->getControllerName()) )
        {
            \Ninja::$errorReporter->add("Could not find <em>class `{$request->getControllerName()}`</em> in <em>`$controllerDirPath{$controllerFile}`</em>", 9)
                                 ->terminate();
        }

        // If the function name begins with an underscore _
        if ( substr($request->getActionName(), 0, 1) == '_' )
        {
            \Ninja::$errorReporter->add("The action <em>`{$request->getActionName()}()`</em> function (if exists) in <b>`$controllerDirPath{$controllerFile}`</b> is for private access only." , 2 )
                                 ->terminate();
        }


        // If the controller class has not implemented the \Ninja\Controller parent class
        if( ! is_subclass_of($request->getControllerName(), '\Ninja\Controller') )
        {
            \Ninja::$errorReporter->add("Class <em>`{$request->getControllerName()}`</em> in <em>`$controllerDirPath{$controllerFile}`</em> does not extend the <em>`Ninja_Controller`</em> class.", 3)
                                 ->terminate();
        }

        // If the action exists in the Controller
        if( method_exists($request->getControllerName(), $request->getActionName()) )
        {
            $controllerName = $request->getControllerName();
            /**
             * Create instance of controller class
             * @var $controller \Ninja\Controller
             */
            $controller = new $controllerName($request, $response);

            /**
             * The reflection of class object (ReflectionClass instance)
             * @var $class \ReflectionObject
             */
            $class = new \ReflectionObject($controller);
            /**
             * The reflection of class's method (ReflectionMethod instance)
             * @var $method \ReflectionMethod
             */
            $method = $class->getMethod($request->getActionName());


            if( $method->isStatic() )
            {// check if static method
                \Ninja::$errorReporter->add("The requested action <em>`{$request->getActionName()}()`</em> cannot be a static function in <em>`{$controllerFile}`</em>", 7)
                                     ->terminate();
            }

            if( ! $method->isPublic() )
            { // if method not public, call remap if exists

                if( method_exists($request->getControllerName(), '_remap') )
                {
                    $controller->_before(); // Call pre-action
                    // if an _remap() function exists, then call the remap function
                    call_user_func_array( array($controller, '_remap'), array($request->getActionName(), $request->getActionParams()) );
                    $controller->_after(); // Call post-action
                }
                else //if no remap method found either
                {
                    \Ninja::$errorReporter->add("The requested action <em>`{$request->getActionName()}()`</em> in <em>`{$controllerFile}`</em> is <em>NOT</em> public.", 8)
                                         ->terminate();
                }

            }
            else if( $request->getActionParamCount() < $method->getNumberOfParameters() )
            {
                // Check if total params is lesser than the params required by the function
                \Ninja::$errorReporter->add("The action <em>`{$request->getActionName()}()`</em> in <em>`{$controllerFile}`</em> requires a minimum of `" . $method->getNumberOfParameters() . "` parameter(s).", 1)
                                     ->terminate();
            }
            else
            {
                /**
                 * If total params passed is equal to or greater than the params required by the action
                 */

                $controller->_before(); // Call pre-action
                call_user_func_array( array($controller, $request->getActionName()), $request->getActionParams() ); // Call action
                $controller->_after(); // Call post-action
            }
        }
        else
        {	// If the action name does not exist in the controller

            // check for an _remap function?
            if( method_exists($request->getControllerName(), "_remap") )
            {
                // if an _remap() function exists, then call the remap function
                // Signature: function _remap($methodName, $parameters)

                $controllerName = $request->getControllerName();
                /**
                 * Create instance of controller class
                 * @var $controller \Ninja\Controller
                 */
                $controller = new $controllerName($request, $response);

                $controller->_before(); // Call pre-action
                call_user_func_array( array($controller, '_remap'),  array($request->getActionName(), $request->getActionParams()) );
                $controller->_after(); // Call post-action
            }
            else
            {//if no method found & no _remap() method found

                \Ninja::$errorReporter->add("The requested action <em>`{$request->getActionName()}()`</em> does not exist in <em>`{$controllerFile}`</em>.", 0)
                                     ->terminate();
            }
        }

        
    }
}
