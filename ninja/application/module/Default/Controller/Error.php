<?php
namespace Controller;

/**
 * The Error Controller that handles all exceptions and errors
 */
class Error extends \Ninja\Controller\Error
{
    public function index()
    {
        /**
         * You can access the following information:
         *
         *  - Exception thrown: $this->request->getException()
         *  - Original Request which caused the exception: $this->request->getErrorRequest();
         */

        /**
         * You can control behavior based on the exception/error class thrown
         */
        switch (get_class($this->request->getException())) // Remember, using get_class, there's no prefix '\'. Go php!
        {
            case 'Ninja\Filesystem\Exception':
                echo "File or resource not found";
                return;
                break;
        }

        /**
         * If no match above, behavior can be controlled by the error code too
         */
        switch ($this->request->getException()->getCode())
        {
            case 404:
                $this->response->setHttpResponseCode(404);
                $this->show404();
                break;

            case 503:
            default:
                $this->response->setHttpResponseCode(503);
                $this->show503();
                break;
        }
    }

    /**
     * 404 Page Not Found
     *
     * @return void
     */
    public function show404()
    {
        $view = new \Ninja\View('error/404.php');
        echo $view;
    }

    /**
     * 503 Service Unavailable
     *
     * @return void
     */
    public function show503()
    {
        $view = new \Ninja\View('error/503.php');
        echo $view;
    }
}
