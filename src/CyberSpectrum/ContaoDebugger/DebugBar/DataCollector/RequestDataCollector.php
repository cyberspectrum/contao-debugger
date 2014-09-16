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

/**
 * Collects info about the current request
 */
class RequestDataCollector extends \DebugBar\DataCollector\RequestDataCollector
{
    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $vars = array('_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER');
        $data = array();

        foreach ($vars as $var) {
            if (isset($GLOBALS[$var])) {
                $data['$' . $var] = $this->getDataFormatter()->formatVar($GLOBALS[$var]);
            }
        }

        $data['outgoing headers'] = $this->getDataFormatter()->formatVar(headers_list());
        $constants                = get_defined_constants(true);
        $data['constants']        = $this->getDataFormatter()->formatVar($constants['user']);

        return $data;
    }
}
