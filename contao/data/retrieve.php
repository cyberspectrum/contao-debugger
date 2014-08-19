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

define('TL_MODE', 'BE');

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

require_once $dir . '/system/initialize.php';

// @codingStandardsIgnoreStart - We want to access the $_GET array.
if (isset($_GET['asset']))
{
	\CyberSpectrum\ContaoDebugger\Debugger::generateAsset($_GET['asset']);
	return;
}
// @codingStandardsIgnoreEnd
\CyberSpectrum\ContaoDebugger\Debugger::getPersisted();
