<?php

if (version_compare(PHP_VERSION, '5.3', '<'))
{
    // Clear out the cache to prevent errors. This typically happens on Windows/FastCGI.
    clearstatcache();
}
else
{
    // Clearing the realpath() cache is only possible PHP 5.3+
    clearstatcache(TRUE);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Ninja Installation</title>

    <style type="text/css">
        body { width: 42em; margin: 0 auto; font-family: sans-serif; background: #fff; font-size: 1em; }
        h1 { letter-spacing: -0.04em; }
        h1 + p { margin: 0 0 2em; color: #333; font-size: 90%; font-style: italic; }
        code { font-family: monaco, monospace; }
        table { border-collapse: collapse; width: 100%; }
        table th,
        table td { padding: 0.4em; text-align: left; vertical-align: top; }
        table th { width: 12em; font-weight: normal; }
        table tr:nth-child(odd) { background: #eee; }
        table td.pass { color: #191; }
        table td.fail { color: #911; }
        #results { padding: 0.8em; color: #fff; font-size: 1.5em; }
        #results.pass { background: #191; }
        #results.fail { background: #911; }
    </style>

</head>
<body>

<h1>Environment Tests</h1>

<p>
    The following tests have been run to determine if <a href="https://github.com/epicwhale/ninja">Ninja</a> will work in your environment.
    If any of the tests have failed, consult the <a href="https://github.com/epicwhale/ninja/wiki/Installation">documentation</a>
    for more information on how to correct the problem.
</p>

<?php $failed = FALSE ?>

<table cellspacing="0">
    <tr>
        <th>PHP Version</th>
        <?php if (version_compare(PHP_VERSION, '5.3.0', '>=')): ?>
        <td class="pass"><?php echo PHP_VERSION ?></td>
        <?php else: $failed = TRUE ?>
        <td class="fail">Ninja requires PHP 5.2.3 or newer, this version is <?php echo PHP_VERSION ?>.</td>
        <?php endif ?>
    </tr>

    <tr>
        <th>PCRE UTF-8</th>
        <?php if ( ! @preg_match('/^.$/u', 'ñ')): $failed = TRUE ?>
        <td class="fail"><a href="http://php.net/pcre">PCRE</a> has not been compiled with UTF-8 support.</td>
        <?php elseif ( ! @preg_match('/^\pL$/u', 'ñ')): $failed = TRUE ?>
        <td class="fail"><a href="http://php.net/pcre">PCRE</a> has not been compiled with Unicode property support.</td>
        <?php else: ?>
        <td class="pass">Pass</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>SPL Enabled</th>
        <?php if (function_exists('spl_autoload_register')): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">PHP <a href="http://www.php.net/spl">SPL</a> is either not loaded or not compiled in.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>Reflection Enabled</th>
        <?php if (class_exists('ReflectionClass')): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">PHP <a href="http://www.php.net/reflection">reflection</a> is either not loaded or not compiled in.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>Filters Enabled</th>
        <?php if (function_exists('filter_list')): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">The <a href="http://www.php.net/filter">filter</a> extension is either not loaded or not compiled in.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>Iconv Extension Loaded</th>
        <?php if (extension_loaded('iconv')): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">The <a href="http://php.net/iconv">iconv</a> extension is not loaded.</td>
        <?php endif ?>
    </tr>
    <?php if (extension_loaded('mbstring')): ?>
    <tr>
        <th>Mbstring Not Overloaded</th>
        <?php if (ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING): $failed = TRUE ?>
        <td class="fail">The <a href="http://php.net/mbstring">mbstring</a> extension is overloading PHP's native string functions.</td>
        <?php else: ?>
        <td class="pass">Pass</td>
        <?php endif ?>
    </tr>
    <?php endif ?>
    <tr>
        <th>Character Type (CTYPE) Extension</th>
        <?php if ( ! function_exists('ctype_digit')): $failed = TRUE ?>
        <td class="fail">The <a href="http://php.net/ctype">ctype</a> extension is not enabled.</td>
        <?php else: ?>
        <td class="pass">Pass</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>URI Determination</th>
        <?php if (isset($_SERVER['REQUEST_URI']) OR isset($_SERVER['PHP_SELF']) OR isset($_SERVER['PATH_INFO'])): ?>
        <td class="pass">Pass</td>
        <?php else: $failed = TRUE ?>
        <td class="fail">Neither <code>$_SERVER['REQUEST_URI']</code>, <code>$_SERVER['PHP_SELF']</code>, or <code>$_SERVER['PATH_INFO']</code> is available.</td>
        <?php endif ?>
    </tr>

    <?php
        $modules =false;
        if (function_exists('apache_get_modules'))
        {
            $modules = apache_get_modules();
            $mod_rewrite = in_array('mod_rewrite', $modules);
        }
        else
        {
            $mod_rewrite =  getenv('HTTP_MOD_REWRITE')=='On' ? true : false ;
        }
    ?>
    <tr>
        <th>mod_rewrite Enabled</th>
        <?php if ($modules): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja requires <a href="http://httpd.apache.org/docs/current/mod/mod_rewrite.html">mod_rewrite</a> for clean URL's.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>MySQL Enabled</th>
        <?php if (function_exists('mysql_connect')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja can use the <a href="http://php.net/mysql">MySQL</a> extension to support MySQL databases.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>PDO Enabled</th>
        <?php if (class_exists('PDO')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja can use <a href="http://php.net/pdo">PDO</a> to support additional databases.</td>
        <?php endif ?>
    </tr>
</table>

<?php if ($failed === TRUE): ?>
<p id="results" class="fail">✘ Ninja may not work correctly with your environment.</p>
    <?php else: ?>
<p id="results" class="pass">✔ Your environment passed all requirements.<br />
    Remove or rename the <code>install.php </code> file now.</p>
    <?php endif ?>

<h1>Optional Tests</h1>

<p>
    The following extensions are not required to run the Ninja core, but if enabled can provide access to additional classes.
</p>

<table cellspacing="0">

    <tr>
        <th>PECL HTTP Enabled</th>
        <?php if (extension_loaded('http')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja can use the <a href="http://php.net/http">http</a> extension for the Request_Client_External class.</td>
        <?php endif ?>
    </tr>

    <tr>
        <th>cURL Enabled</th>
        <?php if (extension_loaded('curl')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja can use the <a href="http://php.net/curl">cURL</a> extension for the Request_Client_External class.</td>
        <?php endif ?>
    </tr>

    <tr>
        <th>mcrypt Enabled</th>
        <?php if (extension_loaded('mcrypt')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja requires <a href="http://php.net/mcrypt">mcrypt</a> for the Encrypt class.</td>
        <?php endif ?>
    </tr>
    <tr>
        <th>GD Enabled</th>
        <?php if (function_exists('gd_info')): ?>
        <td class="pass">Pass</td>
        <?php else: ?>
        <td class="fail">Ninja requires <a href="http://php.net/gd">GD</a> v2 for the Image class.</td>
        <?php endif ?>
    </tr>

</table>

</body>
</html>