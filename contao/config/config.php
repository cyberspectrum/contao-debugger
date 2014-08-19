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

define('CONTAO_DEBUGGER_DEBUG_EVENTS', true);
//define('CONTAO_DEBUGGER_DEBUG_PROFILING', true);

if (defined('CONTAO_DEBUGGER_DEBUG_EVENTS') && CONTAO_DEBUGGER_DEBUG_EVENTS)
{
	$GLOBALS['TL_EVENTS']['ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcher'][] =
		'CyberSpectrum\ContaoDebugger\Events\DebuggedEventDispatcher::register';
};

$GLOBALS['debugger-panels']['database'] = function($debugger)
{
	return new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoSQLCollector($debugger['time']);
};

$GLOBALS['debugger'] = CyberSpectrum\ContaoDebugger\Debugger::boot();
