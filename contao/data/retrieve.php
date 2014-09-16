<?php
/**
 * Contao Debugger
 *
 * Copyright (c) 2014 Christian Schiffler
 *
 * @package     ContaoDebugger
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   2014 CyberSpectrum
 * @license     LGPL-3+
 * @filesource
 */

// Search the initialize.php.
$dir = dirname($_SERVER['SCRIPT_FILENAME']);

while ($dir != '.' && $dir != '/' && !is_file($dir . '/system/initialize.php'))
{
    $dir = dirname($dir);
}

if (!is_file($dir . '/system/initialize.php'))
{
    echo 'Could not find initialize.php, where is Contao?';
    exit;
}

define('TL_ROOT', $dir);

while ($dir != '.' && $dir != '/')
{
    if (is_file($dir . '/composer/vendor/autoload.php'))
    {
        $file = $dir . '/composer/vendor/autoload.php';
        break;
    }

    if (is_file($dir . '/vendor/autoload.php'))
    {
        $file = $dir . '/vendor/autoload.php';
        break;
    }

    $dir = dirname($dir);
}

if (!(isset($file) && is_file($file)))
{
    echo 'Could not find autoload.php, where is Composer?';
    exit;
}

require_once $file;

\CyberSpectrum\ContaoDebugger\Debugger::getPersisted();
