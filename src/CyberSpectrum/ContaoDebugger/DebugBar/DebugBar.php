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

use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\BenchmarkCollector;
use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoAutoloaderCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * General purpose debugger for Contao.
 */
class DebugBar extends \DebugBar\DebugBar
{
	/**
	 * {@inheritDoc}
	 */
	public function __construct()
	{
		$time = new TimeDataCollector(DEBUG_START);
		$time->startMeasure('Debugger active.');
		$autoloader = new ContaoAutoloaderCollector();
		$messages   = new MessagesCollector();
		$phpInfo    = new PhpInfoCollector();
		$request    = new RequestDataCollector();
		$memory     = new MemoryCollector();
		$exception  = new ExceptionsCollector();

		$this
			->addCollector($messages)
			->addCollector($time)
			->addCollector($phpInfo)
			->addCollector($request)
			->addCollector($memory)
			->addCollector($exception)
			->addCollector($autoloader);

		foreach ($GLOBALS['debugger-panels'] as $panelFunc)
		{
			$this->addCollector($panelFunc($this));
		}

		if (defined('CONTAO_DEBUGGER_DEBUG_PROFILING') && CONTAO_DEBUGGER_DEBUG_PROFILING)
		{
			$this->addCollector(new BenchmarkCollector());
		}
	}
}
