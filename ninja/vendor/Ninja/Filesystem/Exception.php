<?php
namespace Ninja\Filesystem;

/**
 * Exception thrown by File and Directory class
 */
class Exception extends \Ninja\Exception
{
    /**
     * Initializes constructor with replacement using sprintf.
     * The sprintf compatibility is to work with the refactor Flourish filesystem, file and directory class
     * @param string $message
     */
	public function __construct($message='')
	{
		$args          = array_slice(func_get_args(), 1);
        parent::__construct(vsprintf($message, $args));
	}    
}