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
class EventDispatcherCollector extends MessagesCollector
{
	/**
	 * Create a new instance.
	 */
	public function __construct()
	{
		parent::__construct('event-dispatcher');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMessages()
	{
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

	/**
	 * {@inheritDoc}
	 */
	public function log($level, $message, array $context = array())
	{
		$this->addMessage($message, $level);
	}

	/**
	 * Deletes all messages.
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->messages = array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function collect()
	{
		$messages = $this->getMessages();
		return array(
			'count' => count($messages),
			'messages' => $messages
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getWidgets()
	{
		$name = $this->getName();
		return array(
			$name            => array(
				'icon'       => 'list-alt',
				'widget'     => 'PhpDebugBar.Widgets.MessagesWidget',
				'map'        => $name . '.messages',
				'default'    => '[]'
			),
			$name . ':badge' => array(
				'map'        => $name . '.count',
				'default'    => 'null'
			)
		);
	}
}
