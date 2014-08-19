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

namespace CyberSpectrum\ContaoDebugger\DebugBar\DataCollector;

use DebugBar\DataCollector\MessagesCollector;

/**
 * Provides a way to log messages
 */
class ContaoAutoloaderCollector extends MessagesCollector
{
	/**
	 * Create a new instance.
	 */
	public function __construct()
	{
		parent::__construct('contao-autoloader');
		// We need the debug mode as otherwise the \ClassLoader will not log.
		\Config::set('debugMode', true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessages()
	{
		if (!empty($GLOBALS['TL_DEBUG']['classes_aliased']))
		{
			foreach ((array)$GLOBALS['TL_DEBUG']['classes_aliased'] as $class)
			{
				preg_match_all('#(.*)\s<.*\((.*)\)#', $class, $matches, PREG_SET_ORDER);

				$this->addMessage($matches[0][2] . ' aliased to ' . $matches[0][1], 'aliases');
			}
		}

		if (!empty($GLOBALS['TL_DEBUG']['classes_set']))
		{
			foreach ((array)$GLOBALS['TL_DEBUG']['classes_set'] as $class)
			{
				$this->addMessage($class, 'autoload.php classmap');
			}
		}

		foreach (get_included_files() as $file)
		{
			$this->addMessage($file, 'includes');
		}

		$messages = $this->messages;

		// Sort messages by their timestamp.
		usort($messages, function($a, $b)
		{
			if ($a['time'] === $b['time'])
			{
				return 0;
			}
			return (($a['time'] < $b['time']) ? -1 : 1);
		});

		return $messages;
	}
}
