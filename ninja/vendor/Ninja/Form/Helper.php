<?php
namespace Ninja\Form;

/**
 * A class to help with forms
 * No LTS for this class.
 * @deprecated
 */
class Helper
{
	/**
	* Returns a GET variable.
	* If not set, return a default value.
	* 
	* @param string $key
	* @param mixed $default default value if not set?
	* @return mixed Get value or Default value if not set
	*/
	public static function get($key, $default = NULL)
	{
		return isset($_GET[$key]) ? $_GET[$key] : NULL;
	}
	
    /**
     * Returns a GET variable and trims it.
     * If not set, return a default value.
     *
     * @static
     * @param string $key
     * @param mixed $default
     * @return mixed|string
     */
	public static function getTrim($key, $default = NULL)
	{
		$val = self::get($key, $default);
		return ($val === $default) ? $val : trim($val);
	}
	
	/**
	* Returns a POST variable.
	* If not set, return a default value.
	* 
	* @param string $key
	* @param mixed $default default value if not set?
	* @return string|mixed Get value or Default value if not set
	*/
	public static function post($key, $default = NULL)
	{
		return isset($_POST[$key]) ? $_POST[$key] : NULL;
	}
	
    /**
     * Returns a POST variable and trims it.
     * If not set, return a default value.
     *
     * @static
     * @param $key
     * @param null $default
     * @return mixed|string
     */
	public static function postTrim($key, $default = NULL)
	{
		$val = self::post($key, $default);
		return ($val === $default) ? $val : trim($val);
	}
	

	/**
	* Checks if one or more POST variables are set.
	* Pass multiple parameters if more than one
	* 
	* @param string $key
	* @return boolean
	*/
	public static function isPost($key)
	{
		if(func_num_args() > 0)
		{
			$keys = func_get_args();
			foreach($keys as $key)
			{
				if( ! isset($_POST[$key]) )
					return false;
			}
			return true;
		}
	}
	
	/**
	* Checks if one or more GET variables are set.
	* Pass multiple parameters if more than one
	* 
	* @param string $key
	* @return boolean
	*/
	public static function isGet($key)
	{
		if(func_num_args() > 0)
		{
			$keys = func_get_args();
			foreach($keys as $key)
			{
				if( ! isset($_GET[$key]) )
					return false;
			}
			return true;
		}
	}
	
	/**
	* Checks if one or more $_FILES are set after upload.
	* 
	* @param string $key
	* @return boolean
	*/
	public static function isFileUploaded($key)
	{
		if(func_num_args() > 0)
		{
			$keys = func_get_args();
			foreach($keys as $key)
			{
				if( ! isset($_FILES[$key]) )
					return false;
			}
			return true;
		}
	}
}