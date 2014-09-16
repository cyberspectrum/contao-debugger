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

if (!\Config::get('debugMode')) {
    return;
}

define('CONTAO_DEBUGGER_DEBUG_EVENTS', true);
// define('CONTAO_DEBUGGER_DEBUG_PROFILING', true);

if (defined('CONTAO_DEBUGGER_DEBUG_EVENTS') && CONTAO_DEBUGGER_DEBUG_EVENTS) {
    $GLOBALS['TL_EVENTS']['ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcher'][] =
        'CyberSpectrum\ContaoDebugger\Events\DebuggedEventDispatcher::register';
};

$GLOBALS['debugger-panels']['hooks'] = function ($debugger) {
    /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
    $collector = new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\HookInspectionCollector();
    \CyberSpectrum\ContaoDebugger\HookInspection\HookRegistry::attach($collector, $debugger->getCollector('time'));
    return $collector;
};

$GLOBALS['debugger-panels']['templates'] = function () {
    $collector = new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\TemplateInspectionCollector();
    \CyberSpectrum\ContaoDebugger\Templates\TemplateDebugger::attach($collector);
    return $collector;
};

$GLOBALS['debugger-panels']['database'] = function ($debugger) {
    /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
    return new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoSQLCollector($debugger->getCollector('time'));
};

$GLOBALS['debugger-panels']['contao-autoloader'] = function ($debugger) {
    /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
    $debugger->registerStopFunction(
        function ($debugger) {
            /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
            if ($debugger->hasCollector('contao-autoloader')
                && $autoLoader = $debugger->getCollector('contao-autoloader')
            ) {
                /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoAutoloaderCollector $autoLoader */
                $autoLoader->stop();
            }
        }
    );
    /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
    return new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoAutoloaderCollector();
};

if (defined('CONTAO_DEBUGGER_DEBUG_PROFILING') && CONTAO_DEBUGGER_DEBUG_PROFILING) {
    $GLOBALS['debugger-panels']['benchmark'] = function ($debugger) {
        /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
        $debugger->registerStopFunction(
            function ($debugger) {
                /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugger */
                if ($debugger->hasCollector('benchmark') && $benchmark = $debugger->getCollector('benchmark')) {
                    /** @var \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\BenchmarkCollector $benchmark */
                    $benchmark->stopProfiling();
                }
            }
        );

        return new \CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\BenchmarkCollector();
    };
}

$GLOBALS['debugger'] = CyberSpectrum\ContaoDebugger\Debugger::boot();
