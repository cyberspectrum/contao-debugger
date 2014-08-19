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

namespace CyberSpectrum\ContaoDebugger\DebugBar;

use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * General purpose debugger for Contao.
 */
class DebugBar extends \DebugBar\DebugBar
{
	/**
	 * Functions to be called when the Debugger shall not collect any data anymore.
	 *
	 * @var callable
	 */
	protected $stopFunctions = array();

	/**
	 * {@inheritDoc}
	 */
	public function __construct()
	{
		$time = new TimeDataCollector(DEBUG_START);
		$time->startMeasure('Debugger active.');
		$messages  = new MessagesCollector();
		$phpInfo   = new PhpInfoCollector();
		$request   = new RequestDataCollector();
		$memory    = new MemoryCollector();
		$exception = new ExceptionsCollector();

		$this
			->addCollector($messages)
			->addCollector($time)
			->addCollector($phpInfo)
			->addCollector($request)
			->addCollector($memory)
			->addCollector($exception);

		foreach ($GLOBALS['debugger-panels'] as $panelFunc)
		{
			$collector = $panelFunc($this);
			if ($collector)
			{
				$this->addCollector($collector);
			}
		}
	}

	/**
	 * Register a method to be called when the debugger is shutting down.
	 *
	 * @param callable $function The function to call.
	 *
	 * @return DebugBar
	 */
	public function registerStopFunction($function)
	{
		$this->stopFunctions[] = $function;

		return $this;
	}

	/**
	 * Call the registered methods to advise the collectors to not accept any further data.
	 *
	 * @return DebugBar
	 */
	public function stopCollectors()
	{
		foreach ($this->stopFunctions as $function)
		{
			$function($this);
		}

		return $this;
	}
}
