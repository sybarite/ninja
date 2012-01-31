<?php
namespace Ninja;

/**
* Configuration related
*/
class Config
{
    /**
     * Merges two arrays into one recursively.
     * 
     * @static
     * @param array $a array to be merged to
     * @param array $b array to be merged from
     * @return array
     */
	public static function mergeArray($a,$b)
	{
			//Credit: http://www.yiiframework.com/doc/api/1.1/CMap
	        foreach($b as $k=>$v)
	        {
	                if(is_integer($k))
	                        $a[]=$v;
	                else if(is_array($v) && isset($a[$k]) && is_array($a[$k]))
	                        $a[$k]= self::mergeArray($a[$k],$v);
	                else
	                        $a[$k]=$v;
	        }
	        return $a;
	}
	
    /**
     * Gets a value from an array using a dot separated path.
     *
     *     // Get the value of $array['foo']['bar']
     *     $value = Config::path($array, 'foo.bar');
     *
     * Using a wildcard "*" will search intermediate arrays and return an array.
     *
     *     // Get the values of "color" in theme
     *     $colors = Arr::path($array, 'theme.*.color');
     *
     *     // Using an array of keys
     *     $colors = Arr::path($array, array('theme', '*', 'color'));
     *
     * @static
     * @param $array array to search
     * @param $path key path string (delimiter separated) or array of keys
     * @param null $default default value if the path is not set
     * @param string $delimiter key path delimiter
     * @return array|null
     */
	public static function path($array, $path, $default = NULL, $delimiter = '.')
	{
		
		//Credit: Kohana_Arr class
		
		if ( ! is_array($array) )
		{
			// This is not an array!
			return $default;
		}

		if (is_array($path))
		{
			// The path has already been separated into keys
			$keys = $path;
		}
		else
		{
			if (array_key_exists($path, $array))
			{
				// No need to do extra processing
				return $array[$path];
			}

			// Remove starting delimiters and spaces
			$path = ltrim($path, "{$delimiter} ");

			// Remove ending delimiters, spaces, and wildcards
			$path = rtrim($path, "{$delimiter} *");

			// Split the keys by delimiter
			$keys = explode($delimiter, $path);
		}

		do
		{
			$key = array_shift($keys);

			if (ctype_digit($key))
			{
				// Make the key an integer
				$key = (int) $key;
			}

			if (isset($array[$key]))
			{
				if ($keys)
				{
					if ( is_array($array[$key]) )
					{
						// Dig down into the next part of the path
						$array = $array[$key];
					}
					else
					{
						// Unable to dig deeper
						break;
					}
				}
				else
				{
					// Found the path requested
					return $array[$key];
				}
			}
			elseif ($key === '*')
			{
				// Handle wildcards

				$values = array();
				foreach ($array as $arr)
				{
					if ($value = self::path($arr, implode('.', $keys)))
					{
						$values[] = $value;
					}
				}

				if ($values)
				{
					// Found the values requested
					return $values;
				}
				else
				{
					// Unable to dig deeper
					break;
				}
			}
			else
			{
				// Unable to dig deeper
				break;
			}
		}
		while ($keys);

		// Unable to find the value requested
		return $default;
	}
	
	
}