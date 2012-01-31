<?php
namespace Ninja;

/**
 * Represents a directory on the filesystem, also provides static directory-related methods
 */
class Directory
{
	/**
	 * Creates a directory on the filesystem and returns an object representing it
	 * 
	 * The directory creation is done recursively, so if any of the parent
	 * directories do not exist, they will be created.
	 * 
	 * This operation will be reverted by a filesystem transaction being rolled back.
	 * 
	 * @throws Filesystem\Exception  When no directory was specified, or the directory already exists
	 * 
	 * @param  string  $directory  The path to the new directory
	 * @param  numeric $mode       The mode (permissions) to use when creating the directory. This should be an octal number (requires a leading zero). This has no effect on the Windows platform.
	 * @return \Ninja\Directory
	 */
	public static function create($directory, $mode=0777)
	{
		if (empty($directory)) {
			throw new Filesystem\Exception('No directory name was specified');
		}
		
		if (file_exists($directory)) {
			throw new Filesystem\Exception(
				'The directory specified, %s, already exists',
				$directory
			);
		}
		
		$parent_directory = \Ninja\Filesystem::getPathInfo($directory, 'dirname');
		if (!file_exists($parent_directory)) {
			\Ninja\Directory::create($parent_directory, $mode);
		}
		
		if (!is_writable($parent_directory)) {
			throw new Filesystem\Exception(
				'The directory specified, %s, is inside of a directory that is not writable',
				$directory
			);
		}
		
		mkdir($directory, $mode);
		
		$directory = new \Ninja\Directory($directory);
		
		return $directory;
	}

    /**
     * Gets (if does not exist, recursively creates) a directory on the Filesystem.
     *
     * @throws Filesystem\Exception  When no directory was specified, or the directory already exists
     *
     * @static
     * @param string $directory Path to the directory which you need to be ensured
     * @param int $mode
     * @return \Ninja\Directory
     */
    public static function ensure($directory, $mode=0777)
    {
        try
        {
            return new \Ninja\Directory($directory);
        }
        catch(Filesystem\Exception $e)
        {
            return \Ninja\Directory::create($directory, $mode);
        }
    }
	
	
	/**
	 * Makes sure a directory has a `/` or `\` at the end
	 * 
	 * @param  string $directory  The directory to check
	 * @return string  The directory name in canonical form
	 */
	public static function makeCanonical($directory)
	{
		if (substr($directory, -1) != '/' && substr($directory, -1) != '\\') {
			$directory .= DIRECTORY_SEPARATOR;
		}
		return $directory;
	}
	
	
	/**
	 * A backtrace from when the file was deleted 
	 * 
	 * @var array
	 */
	protected $deleted = NULL;
	
	/**
	 * The full path to the directory
	 * 
	 * @var string
	 */
	protected $directory;
	
	
	/**
	 * Creates an object to represent a directory on the filesystem
	 * 
	 * If multiple \Ninja\Directory objects are created for a single directory,
	 * they will reflect changes in each other including rename and delete
	 * actions.
	 * 
	 * @throws Filesystem\Exception  When no directory was specified, when the directory does not exist or when the path specified is not a directory
	 * 
	 * @param  string  $directory    The path to the directory
	 * @param  boolean $skip_checks  If file checks should be skipped, which improves performance, but may cause undefined behavior - only skip these if they are duplicated elsewhere
	 * @return \Ninja\Directory
	 */
	public function __construct($directory, $skip_checks=FALSE)
	{
		if (!$skip_checks) {
			if (empty($directory)) {
				throw new Filesystem\Exception('No directory was specified');
			}
			
			if (!is_readable($directory)) {
				throw new Filesystem\Exception(
					'The directory specified, %s, does not exist or is not readable',
					$directory
				);
			}
			if (!is_dir($directory)) {
				throw new Filesystem\Exception(
					'The directory specified, %s, is not a directory',
					$directory
				);
			}
		}
		
		$directory = self::makeCanonical(realpath($directory));
		
		$this->directory =& \Ninja\Filesystem::hookFilenameMap($directory);
		$this->deleted   =& \Ninja\Filesystem::hookDeletedMap($directory);
		
		// If the directory is listed as deleted and we are not inside a transaction,
		// but we've gotten to here, then the directory exists, so we can wipe the backtrace
		if ($this->deleted !== NULL) {
			\Ninja\Filesystem::updateDeletedMap($directory, NULL);
		}
	}
	
	
	/**
	 * All requests that hit this method should be requests for callbacks
	 * 
	 * @internal
	 * 
	 * @param  string $method  The method to create a callback for
	 * @return callback  The callback for the method requested
	 */
	public function __get($method)
	{
		return array($this, $method);		
	}
	
	
	/**
	 * Returns the full filesystem path for the directory
	 * 
	 * @return string  The full filesystem path
	 */
	public function __toString()
	{
		return $this->getPath();
	}
	
	
	/**
	 * Removes all files and directories inside of the directory
	 * 
	 * @return void
	 */
	public function clear()
	{
		if ($this->deleted) {
			return;	
		}
		
		foreach ($this->scan() as $file) {
			$file->delete();
		}
	}
	
	
	/**
	 * Will delete a directory and all files and directories inside of it
	 * 
	 * @return void
	 */
	public function delete()
	{
		if ($this->deleted) {
			return;	
		}
		
		$files = $this->scan();
		
		foreach ($files as $file) {
			$file->delete();
		}
		
		rmdir($this->directory);
		
		\Ninja\Filesystem::updateDeletedMap($this->directory, debug_backtrace());
		\Ninja\Filesystem::updateFilenameMapForDirectory($this->directory, '*DELETED at ' . time() . ' with token ' . uniqid('', TRUE) . '* ' . $this->directory);
	}
	
	
	/**
	 * Gets the name of the directory
	 * 
	 * @return string  The name of the directory
	 */
	public function getName()
	{
		return \Ninja\Filesystem::getPathInfo($this->directory, 'basename');
	}
	
	
	/**
	 * Gets the parent directory
	 * 
	 * @return \Ninja\Directory  The object representing the parent directory
	 */
	public function getParent()
	{
		$this->tossIfDeleted();
		
		$dirname = \Ninja\Filesystem::getPathInfo($this->directory, 'dirname');
		
		if ($dirname == $this->directory) {
			throw new Filesystem\Exception(
				'The current directory does not have a parent directory'
			);
		}
		
		return new \Ninja\Directory($dirname);
	}
	
	
	/**
	 * Gets the directory's current path
	 * 
	 * If the web path is requested, uses translations set with
	 * \Ninja\Filesystem::addWebPathTranslation()
	 * 
	 * @param  boolean $translate_to_web_path  If the path should be the web path
	 * @return string  The path for the directory
	 */
	public function getPath($translate_to_web_path=FALSE)
	{
		$this->tossIfDeleted();
		
		if ($translate_to_web_path) {
			return \Ninja\Filesystem::translateToWebPath($this->directory);
		}
		return $this->directory;
	}
	
	
	/**
	 * Gets the disk usage of the directory and all files and folders contained within
	 * 
	 * This method may return incorrect results if files over 2GB exist and the
	 * server uses a 32 bit operating system
	 * 
	 * @param  boolean $format          If the filesize should be formatted for human readability
	 * @param  integer $decimal_places  The number of decimal places to format to (if enabled)
	 * @return integer|string  If formatted, a string with filesize in b/kb/mb/gb/tb, otherwise an integer
	 */
	public function getSize($format=FALSE, $decimal_places=1)
	{
		$this->tossIfDeleted();
		
		$size = 0;
		
		$children = $this->scan();
		foreach ($children as $child) {
			$size += $child->getSize();
		}
		
		if (!$format) {
			return $size;
		}
		
		return \Ninja\Filesystem::formatFilesize($size, $decimal_places);
	}
	
	
	/**
	 * Check to see if the current directory is writable
	 * 
	 * @return boolean  If the directory is writable
	 */
	public function isWritable()
	{
		$this->tossIfDeleted();
		
		return is_writable($this->directory);
	}
	
	
	/**
	 * Moves the current directory into a different directory
	 * 
	 * Please note that ::rename() will rename a directory in its current
	 * parent directory or rename it into a different parent directory.
	 * 
	 * If the current directory's name already exists in the new parent
	 * directory and the overwrite flag is set to false, the name will be
	 * changed to a unique name.
	 * 
	 *
	 * @throws Filesystem\Exception  When the new parent directory passed is not a directory, is not readable or is a sub-directory of this directory
	 * 
	 * @param  \Ninja\Directory|string $new_parent_directory  The directory to move this directory into
	 * @param  boolean           $overwrite             If the current filename already exists in the new directory, `TRUE` will cause the file to be overwritten, `FALSE` will cause the new filename to change
	 * @return \Ninja\Directory  The directory object, to allow for method chaining
	 */
	public function move($new_parent_directory, $overwrite)
	{
		if (!$new_parent_directory instanceof \Ninja\Directory) {
			$new_parent_directory = new \Ninja\Directory($new_parent_directory);
		}
		
		if (strpos($new_parent_directory->getPath(), $this->getPath()) === 0) {
			throw new Filesystem\Exception('It is not possible to move a directory into one of its sub-directories');
		}
		
		return $this->rename($new_parent_directory->getPath() . $this->getName(), $overwrite);
	}
	
	
	/**
	 * Renames the current directory
	 * 
	 *
	 * @param  string  $new_dirname  The new full path to the directory or a new name in the current parent directory
	 * @param  boolean $overwrite    If the new dirname already exists, TRUE will cause the file to be overwritten, FALSE will cause the new filename to change
	 * @return void
	 */
	public function rename($new_dirname, $overwrite)
	{
		$this->tossIfDeleted();
		
		if (!$this->getParent()->isWritable()) {
			throw new Filesystem\Exception(
				'The directory, %s, can not be renamed because the directory containing it is not writable',
				$this->directory
			);
		}
		
		// If the dirname does not contain any folder traversal, rename the dir in the current parent directory
		if (preg_match('#^[^/\\\\]+$#D', $new_dirname)) {
			$new_dirname = $this->getParent()->getPath() . $new_dirname;	
		}
		
		$info = \Ninja\Filesystem::getPathInfo($new_dirname);
		
		if (!file_exists($info['dirname'])) {
			throw new Filesystem\Exception(
				'The new directory name specified, %s, is inside of a directory that does not exist',
				$new_dirname
			);
		}
		
		if (file_exists($new_dirname)) {
			if (!is_writable($new_dirname)) {
				throw new Filesystem\Exception(
					'The new directory name specified, %s, already exists, but is not writable',
					$new_dirname
				);
			}
			if (!$overwrite) {
				$new_dirname = \Ninja\Filesystem::makeUniqueName($new_dirname);
			}
		} else {
			$parent_dir = new \Ninja\Directory($info['dirname']);
			if (!$parent_dir->isWritable()) {
				throw new Filesystem\Exception(
					'The new directory name specified, %s, is inside of a directory that is not writable',
					$new_dirname
				);
			}
		}

        // NOTE: On windows, if the directory already exists, then a weird Warning is generated
        rename($this->directory, $new_dirname);
		
		// Make the dirname absolute
		$new_dirname = \Ninja\Directory::makeCanonical(realpath($new_dirname));
		
		\Ninja\Filesystem::updateFilenameMapForDirectory($this->directory, $new_dirname);
	}
	
	
	/**
	 * Performs a [http://php.net/scandir scandir()] on a directory, removing the `.` and `..` entries
	 * 
	 * If the `$filter` looks like a valid PCRE pattern - matching delimeters
	 * (a delimeter can be any non-alphanumeric, non-backslash, non-whitespace
	 * character) followed by zero or more of the flags `i`, `m`, `s`, `x`,
	 * `e`, `A`, `D`,  `S`, `U`, `X`, `J`, `u` - then
	 * [http://php.net/preg_match `preg_match()`] will be used.
	 * 
	 * Otherwise the `$filter` will do a case-sensitive match with `*` matching
	 * zero or more characters and `?` matching a single character.
	 * 
	 * On all OSes (even Windows), directories will be separated by `/`s when
	 * comparing with the `$filter`.
	 * 
	 * @param  string $filter  A PCRE or glob pattern to filter files/directories by path - directories can be detected by checking for a trailing / (even on Windows)
	 * @return array  The \Ninja\File (or \Ninja\File_Image) and \Ninja\Directory objects for the files/directories in this directory
	 */
	public function scan($filter=NULL)
	{
		$this->tossIfDeleted();
		
		$files   = array_diff(scandir($this->directory), array('.', '..'));
		$objects = array();
		
		if ($filter && !preg_match('#^([^a-zA-Z0-9\\\\\s]).*\1[imsxeADSUXJu]*$#D', $filter)) {
			$filter = '#^' . strtr(
				preg_quote($filter, '#'),
				array(
					'\\*' => '.*',
					'\\?' => '.'
				)
			) . '$#D';
		}
		
		natcasesort($files);
		
		foreach ($files as $file) {
			if ($filter) {
				$test_path = (is_dir($this->directory . $file)) ? $file . '/' : $file;
				if (!preg_match($filter, $test_path)) {
					continue;
				}
			}
			
			$objects[] = \Ninja\Filesystem::createObject($this->directory . $file);
		}
		
		return $objects;
	}
	
	
	/**
	 * Performs a **recursive** [http://php.net/scandir scandir()] on a directory, removing the `.` and `..` entries
	 * 
	 * @param  string $filter  A PCRE or glob pattern to filter files/directories by path - see ::scan() for details
	 * @return array  The \Ninja\File (or \Ninja\File\Image) and \Ninja\Directory objects for the files/directories (listed recursively) in this directory
	 */
	public function scanRecursive($filter=NULL)
	{
		$this->tossIfDeleted();
		
		$objects = $this->scan();
		
		for ($i=0; $i < sizeof($objects); $i++) {
			if ($objects[$i] instanceof \Ninja\Directory) {
				array_splice($objects, $i+1, 0, $objects[$i]->scan());
			}
		}
		
		if ($filter) {
			if (!preg_match('#^([^a-zA-Z0-9\\\\\s*?^$]).*\1[imsxeADSUXJu]*$#D', $filter)) {
				$filter = '#^' . strtr(
					preg_quote($filter, '#'),
					array(
						'\\*' => '.*',
						'\\?' => '.'
					)
				) . '$#D';
			}
			
			$new_objects  = array();
			$strip_length = strlen($this->getPath());
			foreach ($objects as $object) {
				$test_path = substr($object->getPath(), $strip_length);
				$test_path = str_replace(DIRECTORY_SEPARATOR, '/', $test_path);
				if (!preg_match($filter, $test_path)) {
					continue;	
				}	
				$new_objects[] = $object;
			}
			$objects = $new_objects;
		}
		
		return $objects;
	}
	
	
	/**
	 * Throws an exception if the directory has been deleted
	 * 
	 * @return void
	 */
	protected function tossIfDeleted()
	{
		if ($this->deleted) {
			throw new Filesystem\Exception(
				"The action requested can not be performed because the directory has been deleted."
			);
		}
	}


    // --- Custom functions added by Dayson

    /**
     * Creates a file directly under this directory.
     * 
     * @param string $filename Name of the file to create under this directory
     * @param string $contents
     * @return \Ninja\File
     */
    public function createFile($filename, $contents = '')
    {
        $this->tossIfDeleted();
        return \Ninja\File::create( ($this->getPath() . $filename), $contents);
    }

    /**
     * Gets an existing file under this directory
     *
     * @param string $filename Name of the file to get under this directory
     * @return \Ninja\File
     */
    public function getFile($filename)
    {
        $this->tossIfDeleted();
        return new \Ninja\File( ($this->getPath() . $filename) );
    }

    /**
     * Ensures that a file exists under this directory
     *
     * @param string $filename Name of the file under this directory
     * @param string $contents
     * @return \Ninja\File
     */
    public function ensureFile($filename, $contents = '')
    {
        $this->tossIfDeleted();
        return \Ninja\File::ensure( ($this->getPath() . $filename), $contents );
    }

    /**
     * Checks if a file exists under this directory
     *
     * @param string $filename Filename to check for under this directory
     * @return bool
     */
    public function hasFile($filename)
    {
        $this->tossIfDeleted();
        return file_exists($this->getPath() . $filename);
    }


    /**
     * Creates a directory directly under this directory.
     *
     * @param  string  $directory  The path to the new directory under this directory
	 * @param  numeric $mode       The mode (permissions) to use when creating the directory. This should be an octal number (requires a leading zero). This has no effect on the Windows platform.
     * @return \Ninja\Directory
     */
    public function createDirectory($directoryPath, $mode=0777)
    {
        $this->tossIfDeleted();
        return \Ninja\Directory::create( ($this->getPath() . $directoryPath), $mode);
    }

    /**
     * Ensures a directory under this directory
     *
     * @param  string  $directory  The path to the directory under this directory
	 * @param  numeric $mode       The mode (permissions) to use when creating the directory if it does not exist. This should be an octal number (requires a leading zero). This has no effect on the Windows platform.
     * @return \Ninja\Directory
     */
    public function ensureDirectory($directoryPath, $mode=0777)
    {
        $this->tossIfDeleted();
        return \Ninja\Directory::ensure( ($this->getPath() . $directoryPath), $mode);
    }

    /**
     * Gets an existing directory under this directory
     *
     * @param string $directoryPath
     * @return \Ninja\Directory
     */
    public function getDirectory($directoryPath)
    {
        $this->tossIfDeleted();
        return new \Ninja\Directory( ($this->getPath() . $directoryPath) );
    }

    /**
     * Checks if a file exists under this directory
     *
     * @param string $filename Filename to check for under this directory
     * @return bool
     */
    public function hasDirectory($dirPath)
    {
        $this->tossIfDeleted();
        return is_dir($this->getPath() . $dirPath);
    }

    /**
     * Copy a file (or a list of files) directly into this directory.
     * Note: Any directories passed are ignored.
     *
     * @param \Ninja\File[]|\Ninja\File $files File object or array of File objects to copy into this directory.
     * @param bool $overwrite Whether to overwrite if file with same name found?
     * @return void
     */
    public function pasteFile($files, $overwrite)
    {
        $this->tossIfDeleted();

        // if not array
        if (!is_array($files))
        {
            $f = $files;
            if (is_a($f, '\Ninja\File'))
            {
                if ($overwrite) // if overwrite
                {
                    $f->duplicate($this, TRUE);
                }
                else // if do not overwrite
                {
                    /**
                     * Verify that the dest file does not exist, then only create it
                     * If we do not do this, a unique name is used for a duplicate file by default.
                     * We do not want ANY file to be created in such a case if overwrite is disabled.
                     */
                    if (! $this->hasFile($f->getName()))
                    {
                        $f->duplicate($this, TRUE);
                    }
                }
            }
        }
        else // if array
        {
            foreach ($files as $f)
            {
                $this->pasteFile($f, $overwrite);
            }
        }
    }


    /**
     * Copies all the contents of this directory recursively
     * into the destination directory.
     *
     * @param \Ninja\Directory $destDirectory
     * @return \Ninja\Directory the destination ninja directory
     */
    public function duplicate(\Ninja\Directory $destDirectory, $overwrite)
    {
        $this->tossIfDeleted();

        $srcPath = $this->getPath();
        $destPath = $destDirectory->getPath();

        if (strpos($destPath, $srcPath) === 0)
            throw new Filesystem\Exception("It is not possible to move a directory into itself or one of its sub-directories - $srcPath");

        //if( $srcPath === $destPath )
        //    throw new Filesystem\Exception("Cannot copy directory into itself - $srcPath");

        if( ! $this->_smartCopy($srcPath, $destPath, $overwrite) )
            throw new Filesystem\Exception("Could not duplicate directory $srcPath into $destPath");

        return $destDirectory;
    }


    /**
     * Copies file or folder from source to destination recursively.
     *
     * @param string $source //file or folder
     * @param string $dest ///file or folder
     * @param string $options //folderPermission,filePermission
     * @return boolean
     */
    private function _smartCopy($source, $dest, $overwrite, $options=array())
    {
        // Function adapted from: http://sina.salek.ws/content/unix-smart-recursive-filefolder-copy-function-php

        $result = FALSE;

        // For Cross Platform Compatibility
        if (!isset($options['noTheFirstRun']))
        {
            $source = str_replace('\\','/',$source);
            $dest   = str_replace('\\','/',$dest);
            $options['noTheFirstRun'] = TRUE;
        }

        if (is_file($source))
        {
            $__dest = $dest;

            // if no overwriting
            if (!$overwrite)
            {
                // if file already exists?
                if (is_file($dest))
                {
                    $result = TRUE; // don't do any copy
                }
            }
            else // overwriting true, so blindly copy / replace
            {
                $result = copy($source, $__dest);
                chmod($__dest, fileperms($source));
            }
        }
        elseif (is_dir($source))
        {
            if ($dest[strlen($dest)-1] != '/')
            {
                if ($source[strlen($source)-1] != '/')
                {
                    // Copy parent directory with new name and all its content
                    if ( ! is_dir($dest) )
                    {
                        @mkdir($dest, 0777);
                        //chmod($dest, fileperms($source));
                    }
                }
            }

            $dirHandle = opendir($source);
            while ( $file=readdir($dirHandle) )
            {
                if ( $file != "." && $file != ".." )
                {
                    $__dest   = $dest   . "/" . $file;
                    $__source = $source . "/" . $file;
                    //echo "$__source ||| $__dest<br />";
                    
                    if ( $__source != $dest)
                    {
                        $result=$this->_smartCopy($__source, $__dest, $overwrite, $options);
                    }
                }
            }
            closedir($dirHandle);
        }
        else
        {
            $result = FALSE;
        }
        return $result;
    }

}



/**
 * Copyright (c) 2007-2011 Will Bond <will@flourishlib.com>, others
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
