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

error_reporting(E_ALL);

/**
 * Include the given file if it exists.
 *
 * @param string $file The filename.
 *
 * @return bool|\Composer\Autoload\ClassLoader
 */
// @codingStandardsIgnoreStart - We do not want to put this into a static class.
function includeIfExists($file)
{
	return file_exists($file) ? include $file : false;
}
// @codingStandardsIgnoreEnd

// Check 1. Locally installed dependencies.
// Check 2. We are within an composer install.
if ((!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php'))
	&& (!$loader = includeIfExists(__DIR__.'/../../../autoload.php')))
	{
	echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
		'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
		'php composer.phar install'.PHP_EOL;
	exit(1);
}

$loader->add('CyberSpectrum\ContaoDebugger\Test', __DIR__);
