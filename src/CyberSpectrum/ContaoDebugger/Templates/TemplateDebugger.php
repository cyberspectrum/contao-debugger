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
     * Attach to the collector and HOOKs.
     *
     * @param TemplateInspectionCollector $collector The collector.
     *
     * @return void
     */
    public static function attach($collector)
    {
        self::$collector = $collector;
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
        self::$collector->addTemplate($template);
    }
}
