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
 * Special version of formatter to prevent Contao from replacing the insert tags etc..
 *
 * @package CyberSpectrum\ContaoDebugger\DebugBar\DataFormatter
 */
class DataFormatter extends \DebugBar\DataFormatter\DataFormatter
{
    /**
     * Transforms a PHP variable to a string representation.
     *
     * @param mixed $data The data to format.
     *
     * @return string.
     */
    public function formatVar($data)
    {
        $result = parent::formatVar($data);
        $result = str_replace(
            array('[[', ']]', '{{', '}}'),
            array('&#91;&#91;', '&#93;&#93;', '&#123;&#123;', '&#124;&#124;'),
            $result
        );

        return $result;
    }
}
