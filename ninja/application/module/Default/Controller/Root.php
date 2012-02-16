<?php

namespace Controller;

/**
 * The Root controller is called when the home page of the website is requested.
 */
class Root extends \Ninja\Controller
{
    /**
     * The homepage of the website
     */
    public function index()
    {
        echo "Hello World!";
    }
}