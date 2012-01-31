<?php
namespace Ninja\Console;

/**
 * Class to parse options for command-line application.
 */
class Getopt extends \Zend_Console_Getopt
{
    public function setProgName($name)
    {
        $this->_progname = $name;
    }
}