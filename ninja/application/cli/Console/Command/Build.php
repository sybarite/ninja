<?php
namespace Console\Command;

class Build extends \Ninja\Console\Command
{
    const REVISION = 53;

    public function full()
    {
        \Ninja::$commandRunner->run('build js')
                             ->run('build css');
    }

    /**
     * Minifies all JS files in public directory and adds a revision suffix. Foo.js --> Foo.52.js
     * @return void
     */
    public function js()
    {
        $dir = new \Ninja\Directory(NINJA_DOCROOT . 'public/');
        /**
         * @var $files \Ninja\File[]
         */
        $files = $dir->scanRecursive("*.js");

        echo "\nMinifying Javascript Files...\n";
        foreach($files as $sourceFile)
        {
            // Skip all foo.#.js (e.g foo.52.js) which are files from a previous build
            if (is_numeric(substr($sourceFile->getName(true), strrpos($sourceFile->getName(true), '.') + 1)))
                continue;

            /**
             * @var $destFile \Ninja\File
             */
            $destFile = $sourceFile->getParent()->ensureFile($sourceFile->getName(true) . '.' . self::REVISION . '.'  . $sourceFile->getExtension());

            $destFile->write(\Minify_Js::minify($sourceFile->read()));
            echo '   ' . $sourceFile->getPath() . "\n";

            unset($sourceFile);
        }
    }

    /**
     * Minifies all CSS files in public directory and adds a revision suffix. Foo.css --> Foo.52.css
     * @return void
     */
    public function css()
    {
        $dir = new \Ninja\Directory(NINJA_DOCROOT . 'public/');
        /**
         * @var $files \Ninja\File[]
         */
        $files = $dir->scanRecursive("*.css");

        echo "\nMinifying Css Files...\n";
        foreach($files as $sourceFile)
        {
            // Skip all foo.#.js (e.g foo.52.js) which are files from a previous build
            if (is_numeric(substr($sourceFile->getName(true), strrpos($sourceFile->getName(true), '.') + 1)))
                continue;

            /**
             * @var $destFile \Ninja\File
             */
            $destFile = $sourceFile->getParent()->ensureFile($sourceFile->getName(true) . '.' . self::REVISION . '.'  . $sourceFile->getExtension());

            $destFile->write(\Minify_Css::process($sourceFile->read()));
            echo '   ' . $sourceFile->getPath() . "\n";

            unset($sourceFile);
        }
    }

    /**
     * Delete all files with a revision suffix.
     * @return void
     */
    public function clean()
    {
        $dir = new \Ninja\Directory(NINJA_DOCROOT . 'public/');
        /**
         * @var $files \Ninja\File[]
         */

        $files = $dir->scanRecursive("*.css");
        echo "\nDeleting Minified Css Files...\n";
        foreach($files as $sourceFile)
        {
            // if does not have a numeric pre-extension-suffix foo.#.js (e.g foo.52.js) which are files from a previous build
            if ( ! is_numeric(substr($sourceFile->getName(true), strrpos($sourceFile->getName(true), '.') + 1)) )
                continue;

            echo '   ' . $sourceFile->getPath() . "\n";
            $sourceFile->delete();

            unset($sourceFile);
        }

        $files = $dir->scanRecursive("*.js");
        echo "\nDeleting Minified Js Files...\n";
        foreach($files as $sourceFile)
        {
            // if does not have a numeric pre-extension-suffix foo.#.js (e.g foo.52.js) which are files from a previous build
            if ( ! is_numeric(substr($sourceFile->getName(true), strrpos($sourceFile->getName(true), '.') + 1)) )
                continue;

            echo '   ' . $sourceFile->getPath() . "\n";
            $sourceFile->delete();
            unset($sourceFile);
        }
    }

    
}