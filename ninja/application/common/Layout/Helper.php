<?php

namespace Layout;

/**
 * Helper class with useful html, js, css functions for rendering layouts / view files
 */
class Helper
{
    const ASSETS_DIRECTORY_PATH = 'assets/';

    /**
     * Scripts to be appended to the head
     * 
     * @var array
     */
    protected $_headScripts = array();

    /**
     * Scripts to be added at the footer
     *
     * @var array
     */
    protected $_queuedScripts = array();


    /**
     * Stylesheets to be added to the head
     *
     * @var array
     */
    protected $_stylesheets = array();


    /**
     * Page Title
     *
     * @var string
     */
    protected $_title = '';

    /**
     * Meta Description
     *
     * @var string
     */
    protected $_metaDescription = '';

    /**
     * Returns the base url of this web app
     * @return string
     */
    public function getBaseUrl()
    {
        return \Ninja::$baseUrl;
    }

    /**
     * Prints the base url of the web app
     *
     * @param string $suffix
     * @return void
     */
    public function baseUrl($suffix = '')
    {
        echo $this->getBaseUrl() . $suffix;
    }

    /**
     * Queue a script to be added at the footer of the page.
     *
     * @param $href
     * @param array $options
     * @return Helper
     */
    public function queueScript($href, array $options = array())
    {
        $options['href'] = $href;
        $this->_queuedScripts[] = $options;

        return $this;
    }

    /**
     * Returns the list of queued scripts
     *
     * @return array
     */
    public function getQueuedScripts()
    {
        return $this->_queuedScripts;
    }

    /**
     * Renders all scripts queued to be added
     *
     * @return void
     */
    public function renderQueuedScripts()
    {
        $scriptsList = $this->getQueuedScripts();

        foreach($scriptsList as $script)
        {
            $scriptPath = $this->_expandAssetPath($script['href']);
            echo "<script src=\"$scriptPath\"></script>\n";
        }
    }


    /**
     * Append a script to the head of the page
     *
     * @param $href
     * @param array $options
     * @return Helper
     */
    public function appendScript($href, array $options = array())
    {
        $options['href'] = $href;
        $this->_headScripts[] = $options;

        return $this;
    }

    /**
     * Returns the list of scripts to be appended to head
     *
     * @return array
     */
    public function getHeadScripts()
    {
        return $this->_headScripts;
    }

    /**
     * Renders all scripts queued to be added
     *
     * @return void
     */
    public function renderHeadScripts()
    {
        $scriptsList = $this->getHeadScripts();

        foreach($scriptsList as $script)
        {
            $scriptPath = $this->_expandAssetPath($script['href']);
            echo "<script src=\"$scriptPath\"></script>\n";
        }
    }




    /**
     * Append a stylesheet to the head of the page
     *
     * options:
     *      - conditional: 'lt IE7', 'gte IE 6', etc. Read more at: http://bit.ly/17I1kg
     *
     * @param $href
     * @param array $options
     * @return Helper
     */
    public function appendStylesheet($href, array $options = array())
    {
        $options['href'] = $href;
        $this->_stylesheets[] = $options;

        return $this;
    }

    /**
     * Returns the list of stylesheets to be appended to head
     *
     * @return array
     */
    public function getStylesheets()
    {
        return $this->_stylesheets;
    }

    /**
     * Renders all stylesheets queued to be added to head
     *
     * @return void
     */
    public function renderStylesheets()
    {
        $stylesheets = $this->getStylesheets();

        $baseUrl = $this->getBaseUrl();

        foreach($stylesheets as $options)
        {
            $fullSheetUrl = $this->_expandAssetPath($options['href']);

            if (isset($options['conditional']))
            {
                echo "<!--[if {$options['conditional']}]>";
            }

            echo "<link rel=\"stylesheet\" media=\"screen\" href=\"$fullSheetUrl\">";

            if (isset($options['conditional']))
            {
                echo "<![endif]-->";
            }

            echo "\n";
        }
    }

    /**
     * Returns a file version for this file.
     *     - The file version for now is the filemtime of the file
     *
     * @param $assetPath
     * @return bool|int
     */
    protected function _getFileVersion($assetPath)
    {
        if (!$this->_isLocalFile($assetPath))
            return false;

        $fileSystemPath = NINJA_DOCROOT . $assetPath;

        //if (file_exists($fileSystemPath))
        {
            return filemtime($fileSystemPath);
        }

        //return false;
    }

    /**
     * Whether this file path is a local static path or some remote path?
     *
     * @param $assetPath
     * @return bool
     */
    protected function _isLocalFile($assetPath)
    {
        return (substr($assetPath, 0, strlen(self::ASSETS_DIRECTORY_PATH)) === self::ASSETS_DIRECTORY_PATH);
    }

    /**
     * Expands an asset url to its full absolute url if it's a local file.
     * Also adds a version number suffix if it's a local file.
     *
     * @param $assetPath
     * @return string
     */
    protected function _expandAssetPath($assetPath)
    {
        $isLocalFile = $this->_isLocalFile($assetPath);

        // if a local file?
        if ($isLocalFile)
        {
            $baseUrl = $this->getBaseUrl();
            $fileVersion = $this->_getFileVersion($assetPath);

            return $baseUrl . htmlentities($assetPath) . (($fileVersion !== false) ? "?$fileVersion" : '');
        }

        // if remote file, don't do anything
        return $assetPath;
    }

//    /**
//     * Get the path to the root of the static directory
//     *
//     * @return string
//     */
//    public function getStaticUrl()
//    {
//        return $this->getBaseUrl() . self::STATIC_DIRECTORY_PATH;
//    }
//
//    /**
//     * Output the static url for a file under the static directory
//     *
//     * @param string $file
//     * @return void
//     */
//    public function staticUrl($file = '')
//    {
//        echo $this->getStaticUrl() . $file;
//    }

    public function staticFile($filePath)
    {
        echo $this->_expandAssetPath($filePath);
    }

    public function assetUrl($assetPath = null)
    {
        echo $this->getAssetUrl($assetPath);
    }

    /**
     * Gets the URL for an asset. If no parameter passed,
     * returns the URL to the asset directory.
     *
     * @param null|string $assetPath
     * @return string
     */
    public function getAssetUrl($assetPath = null)
    {
        if (!$assetPath)
        {
            return $this->getBaseUrl() . self::ASSETS_DIRECTORY_PATH;
        }
        else
        {
            return $this->_expandAssetPath(self::ASSETS_DIRECTORY_PATH . $assetPath);
        }
    }

    /**
     * Set the page title
     *
     * @param string $title
     * @return \Layout\Helper
     */
    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    /**
     * Return the page title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Set the meta description
     *
     * @param string $description
     * @return \Layout\Helper
     */
    public function setMetaDescription($description)
    {
        $this->_metaDescription = $description;

        return $this;
    }

    /**
     * Return the page title
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->_metaDescription;
    }


//    public function renderTitle()
//    {
//        echo '<title>' . htmlspecialchars($this->getTitle()) . '</title>';
//    }
}