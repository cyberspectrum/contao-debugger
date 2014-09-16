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

namespace CyberSpectrum\ContaoDebugger;

/**
 * This class provides handy methods for generating debug output.
 */
class DebugTools
{
    /**
     * Reformat an array of arguments as comma separated list.
     *
     * @param array $array The list of arguments.
     *
     * @return string
     */
    public static function argumentList($array)
    {
        if (!$array) {
            return null;
        }

        $result = array();
        foreach ($array as $argument) {
            $result[] = var_export($argument, true);
        }

        return PHP_EOL . implode(', ' . PHP_EOL, $result) . PHP_EOL;
    }

    /**
     * Log a method call.
     *
     * @param object $object The object being called.
     *
     * @param string $method The method being invoked.
     *
     * @param array  $argv   The arguments being passed.
     *
     * @param string $label  The optional label.
     *
     * @return void
     */
    public static function logCall($object, $method, $argv, $label = 'info')
    {
        Debugger::addDebug(sprintf('%s::%s(%s)', get_class($object), $method, self::argumentList($argv)), $label);
    }
}
