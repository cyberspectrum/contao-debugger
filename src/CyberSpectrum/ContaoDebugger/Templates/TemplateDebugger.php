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

namespace CyberSpectrum\ContaoDebugger\Templates;

use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\TemplateInspectionCollector;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Traces Template variables.
 */
class TemplateDebugger
{
    /**
     * The collector to use.
     *
     * @var TemplateInspectionCollector
     */
    protected static $collector;

    /**
     * The time collector.
     *
     * @var TimeDataCollector
     */
    protected static $timeCollector;

    /**
     * Attach to the collector and HOOKs.
     *
     * @param TemplateInspectionCollector $collector     The collector.
     *
     * @param TimeDataCollector           $timeCollector The time collector.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public static function attach($collector, TimeDataCollector $timeCollector = null)
    {
        self::$collector     = $collector;
        self::$timeCollector = $timeCollector;
        array_insert($GLOBALS['TL_HOOKS']['parseTemplate'], 0, array(array(__CLASS__, 'parseTemplate')));
    }

    /**
     * Adds a template.
     *
     * @param \Template $template The message.
     *
     * @return void
     */
    public function parseTemplate(\Template $template)
    {
        if (self::$timeCollector) {
            self::$timeCollector->startMeasure($template->getName(), 'Parse: ' . $template->getName());
            self::$timeCollector->stopMeasure($template->getName());
        }
        self::$collector->addTemplate($template);
    }
}
