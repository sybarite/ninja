<?php
namespace Ninja;

/**
 * Handles filesystem-level tasks including filesystem transactions and the reference map to keep all \Ninja\File and \Ninja\Directory objects in sync
 */
class Filesystem
{
	/**
	 * Maps deletion backtraces to all instances of a file or directory, providing consistency
	 * 
	 * @var array
	 */
	static private $deleted_map = array();
	
	/**
	 * Stores file and directory names by reference, allowing all object instances to be updated at once
	 * 
	 * @var array
	 */
	static private $filename_map = array();
	
	/**
	 * Stores a list of search => replace strings for web path translations
	 * 
	 * @var array
	 */
	static private $web_path_translations = array();
	
	
	/**
	 * Adds a directory to the web path translation list
	 * 
	 * The web path conversion list is a list of directory paths that will be
	 * converted (from the beginning of filesystem paths) when preparing a path
	 * for output into HTML.
	 * 
	 * By default the `$_SERVER['DOCUMENT_ROOT']` will be converted to a blank
	 * string, in essence stripping it from filesystem paths.
	 * 
	 * @param  string $search_path   The path to look for
	 * @param  string $replace_path  The path to replace with
	 * @return void
	 */
	static public function addWebPathTranslation($search_path, $replace_path)
	{
		// Ensure we have the correct kind of slash for the OS being used
		$search_path  = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $search_path);
		$replace_path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $replace_path);
		self::$web_path_translations[$search_path] = $replace_path;
	}
	
	
	/**
	 * Takes a file size including a unit of measure (i.e. kb, GB, M) and converts it to bytes
	 * 
	 * Sizes are interpreted using base 2, not base 10. Sizes above 2GB may not
	 * be accurately represented on 32 bit operating systems.
	 * 
	 * @param  string $size  The size to convert to bytes
	 * @return integer  The number of bytes represented by the size
	 */
	static public function convertToBytes($size)
	{
		if (!preg_match('#^(\d+(?:\.\d+)?)\s*(k|m|g|t)?(ilo|ega|era|iga)?( )?b?(yte(s)?)?$#D', strtolower(trim($size)), $matches)) {
			throw new Filesystem\Exception(
				'The size specified, %s, does not appears to be a valid size',
				$size
			);
		}
		
		if (empty($matches[2])) {
			$matches[2] = 'b';
		}
		
		$size_map = array('b' => 1,
						  'k' => 1024,
						  'm' => 1048576,
						  'g' => 1073741824,
						  't' => 1099511627776);
		return round($matches[1] * $size_map[$matches[2]]);
	}
	
	
	/**
	 * Takes a filesystem path and creates either an \Ninja\Directory, \Ninja\File or \Ninja\File\Image object from it
	 * 
	 * @throws Filesystem\Exception  When no path was specified or the path specified does not exist
	 * 
	 * @param  string $path  The path to the filesystem object
	 * @return \Ninja\Directory|\Ninja\File|\Ninja\File\Image
	 */
	static public function createObject($path)
	{
		if (empty($path)) {
			throw new Filesystem\Exception(
				'No path was specified'
			);
		}
		
		if (!is_readable($path)) {
			throw new Filesystem\Exception(
				'The path specified, %s, does not exist or is not readable',
				$path
			);
		}
		
		if (is_dir($path)) {
			return new \Ninja\Directory($path, TRUE);
		}
		
		if (\Ninja\File\Image::isImageCompatible($path)) {
			return new \Ninja\File\Image($path, TRUE);
		}
		
		return new \Ninja\File($path, TRUE);
	}
	
	
	/**
	 * Takes the size of a file in bytes and returns a friendly size in B/K/M/G/T
	 * 
	 * @param  integer $bytes           The size of the file in bytes
	 * @param  integer $decimal_places  The number of decimal places to display
	 * @return string
	 */
	static public function formatFilesize($bytes, $decimal_places=1)
	{
		if ($bytes < 0) {
			$bytes = 0;
		}
		$suffixes  = array('B', 'K', 'M', 'G', 'T');
		$sizes     = array(1, 1024, 1048576, 1073741824, 1099511627776);
		$suffix    = (!$bytes) ? 0 : floor(log($bytes)/6.9314718);
		return number_format($bytes/$sizes[$suffix], ($suffix == 0) ? 0 : $decimal_places) . ' ' . $suffixes[$suffix];
	}
	
	
	/**
	 * Returns info about a path including dirname, basename, extension and filename
	 * 
	 * @param  string $path     The file/directory path to retrieve information about
	 * @param  string $element  The piece of information to return: `'dirname'`, `'basename'`, `'extension'`, or `'filename'`
	 * @return array  The file's dirname, basename, extension and filename
	 */
	static public function getPathInfo($path, $element=NULL)
	{
		$valid_elements = array('dirname', 'basename', 'extension', 'filename');
		if ($element !== NULL && !in_array($element, $valid_elements)) {
			throw new Filesystem\Exception(
				'The element specified, %1$s, is invalid. Must be one of: %2$s.',
				$element,
				join(', ', $valid_elements)
			);
		}
		
		$path_info = pathinfo($path);
		
		if (!isset($path_info['extension'])) {
			$path_info['extension'] = NULL;
		}
		
		if (!isset($path_info['filename'])) {
			$path_info['filename'] = preg_replace('#\.' . preg_quote($path_info['extension'], '#') . '$#D', '', $path_info['basename']);
		}
		$path_info['dirname'] .= DIRECTORY_SEPARATOR;
		
		if ($element) {
			return $path_info[$element];
		}
		
		return $path_info;
	}
	
	
	/**
	 * Hooks a file/directory into the deleted backtrace map entry for that filename
	 * 
	 * Since the value is returned by reference, all objects that represent
	 * this file/directory always see the same backtrace.
	 * 
	 * @internal
	 * 
	 * @param  string $file  The name of the file or directory
	 * @return mixed  Will return `NULL` if no match, or the backtrace array if a match occurs
	 */
	static public function &hookDeletedMap($file)
	{
		if (!isset(self::$deleted_map[$file])) {
			self::$deleted_map[$file] = NULL;
		}
		return self::$deleted_map[$file];
	}
	
	
	/**
	 * Hooks a file/directory name to the filename map
	 * 
	 * Since the value is returned by reference, all objects that represent
	 * this file/directory will always be update on a rename.
	 * 
	 * @internal
	 * 
	 * @param  string $file  The name of the file or directory
	 * @return mixed  Will return `NULL` if no match, or the exception object if a match occurs
	 */
	static public function &hookFilenameMap($file)
	{
		if (!isset(self::$filename_map[$file])) {
			self::$filename_map[$file] = $file;
		}
		return self::$filename_map[$file];
	}
	
	
	/**
	 * Changes a filename to be safe for URLs by making it all lower case and changing everything but letters, numers, - and . to _
	 * 
	 * @param  string $filename  The filename to clean up
	 * @return string  The cleaned up filename
	 */
	static public function makeURLSafe($filename)
	{
		$filename = strtolower(trim($filename));
		$filename = str_replace("'", '', $filename);
		return preg_replace('#[^a-z0-9\-\.]+#', '_', $filename);	
	}
	
	
	/**
	 * Returns a unique name for a file
	 * 
	 * @param  string $file           The filename to check
	 * @param  string $new_extension  The new extension for the filename, should not include `.`
	 * @return string  The unique file name
	 */
	static public function makeUniqueName($file, $new_extension=NULL)
	{
		$info = self::getPathInfo($file);
		
		// Change the file extension
		if ($new_extension !== NULL) {
			$new_extension = ($new_extension) ? '.' . $new_extension : $new_extension;
			$file = $info['dirname'] . $info['filename'] . $new_extension;
			$info = self::getPathInfo($file);
		}
		
		// If there is an extension, be sure to add . before it
		$extension = (!empty($info['extension'])) ? '.' . $info['extension'] : '';
		
		// Remove _copy# from the filename to start
		$file = preg_replace('#_copy(\d+)' . preg_quote($extension, '#') . '$#D', $extension, $file);
		
		// Look for a unique name by adding _copy# to the end of the file
		while (file_exists($file)) {
			$info = self::getPathInfo($file);
			if (preg_match('#_copy(\d+)' . preg_quote($extension, '#') . '$#D', $file, $match)) {
				$file = preg_replace('#_copy(\d+)' . preg_quote($extension, '#') . '$#D', '_copy' . ($match[1]+1) . $extension, $file);
			} else {
				$file = $info['dirname'] . $info['filename'] . '_copy1' . $extension;
			}
		}
		
		return $file;
	}
	
	
	/**
	 * Updates the deleted backtrace for a file or directory
	 * 
	 * @internal
	 * 
	 * @param  string $file		  A file or directory name, directories should end in `/` or `\`
	 * @param  array  $backtrace  The backtrace for this file/directory
	 * @return void
	 */
	static public function updateDeletedMap($file, $backtrace)
	{
		self::$deleted_map[$file] = $backtrace;
	}
	
	
	/**
	 * Updates the filename map, causing all objects representing a file/directory to be updated
	 * 
	 * @internal
	 * 
	 * @param  string $existing_filename  The existing filename
	 * @param  string $new_filename       The new filename
	 * @return void
	 */
	static public function updateFilenameMap($existing_filename, $new_filename)
	{
		if ($existing_filename == $new_filename) {
			return;
		}
		
		self::$filename_map[$new_filename] =& self::$filename_map[$existing_filename];
		self::$deleted_map[$new_filename]  =& self::$deleted_map[$existing_filename];
		
		unset(self::$filename_map[$existing_filename]);
		unset(self::$deleted_map[$existing_filename]);
		
		self::$filename_map[$new_filename] = $new_filename;
	}
	
	
	/**
	 * Updates the filename map recursively, causing all objects representing a directory to be updated
	 * 
	 * Also updates all files and directories in the specified directory to the new paths.
	 * 
	 * @internal
	 * 
	 * @param  string $existing_dirname  The existing directory name
	 * @param  string $new_dirname       The new dirname
	 * @return void
	 */
	static public function updateFilenameMapForDirectory($existing_dirname, $new_dirname)
	{
		if ($existing_dirname == $new_dirname) {
			return;
		}
		
		// Handle the directory name
		self::$filename_map[$new_dirname] =& self::$filename_map[$existing_dirname];
		self::$deleted_map[$new_dirname]  =& self::$deleted_map[$existing_dirname];
		
		unset(self::$filename_map[$existing_dirname]);
		unset(self::$deleted_map[$existing_dirname]);
		
		self::$filename_map[$new_dirname] = $new_dirname;
		
		// Handle all of the directories and files inside this directory
		foreach (self::$filename_map as $filename => $ignore) {
			if (preg_match('#^' . preg_quote($existing_dirname, '#') . '#', $filename)) {
				$new_filename = preg_replace(
					'#^' . preg_quote($existing_dirname, '#') . '#',
					strtr($new_dirname, array('\\' => '\\\\', '$' => '\\$')),
					$filename
				);
				
				self::$filename_map[$new_filename] =& self::$filename_map[$filename];
				self::$deleted_map[$new_filename]  =& self::$deleted_map[$filename];
				
				unset(self::$filename_map[$filename]);
				unset(self::$deleted_map[$filename]);
				
				self::$filename_map[$new_filename] = $new_filename;
					
			}
		}
	}

	
	/**
	 * Takes a filesystem path and translates it to a web path using the rules added
	 * 
	 * @param  string $path  The path to translate
	 * @return string  The filesystem path translated to a web path
	 */
	static public function translateToWebPath($path)
	{
		$translations = array(realpath($_SERVER['DOCUMENT_ROOT']) => '') + self::$web_path_translations;
		
		foreach ($translations as $search => $replace) {
			$path = preg_replace(
				'#^' . preg_quote($search, '#') . '#',
				strtr($replace, array('\\' => '\\\\', '$' => '\\$')),
				$path
			);
		}
		
		return str_replace('\\', '/', $path);
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return \Ninja\Filesystem
	 */
	private function __construct() { }
}



/**
 * Copyright (c) 2008-2010 Will Bond <will@flourishlib.com>, others
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */