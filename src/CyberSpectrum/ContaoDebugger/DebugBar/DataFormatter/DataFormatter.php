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

namespace CyberSpectrum\ContaoDebugger\DebugBar\DataFormatter;

/**
 * Special version of formatter.
 *
 * @package CyberSpectrum\ContaoDebugger\DebugBar\DataFormatter
 */
class DataFormatter extends \DebugBar\DataFormatter\DataFormatter
{
	/**
	 * {@inheritDoc}
	 */
	protected function kintLite(&$var, $level = 0)
	{
		if (is_object($var))
		{
			return sprintf('object %s (%s)', get_class($var), spl_object_hash($var));
		}

		if (is_string($var) && strpos($var, '[') !== false)
		{
			$var = str_replace(array('[[', ']]'), array('&#91;&#91;', '&#93;&#93;'), $var);
		}

		if (is_string($var) && strlen($var) > 2048)
		{
			return sprintf(
				'string (%s) "%s"...',
				strlen($var),
				htmlspecialchars(substr($var, 0, 2048), ENT_NOQUOTES, 'UTF-8', true)
			);
		}

		return parent::kintLite($var, $level);
	}
}
