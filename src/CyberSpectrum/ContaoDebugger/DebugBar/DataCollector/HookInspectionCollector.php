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
 * Traces HOOK executions.
 */
class HookInspectionCollector extends \DebugBar\DataCollector\RequestDataCollector
{
    /**
     * The messages.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Adds a message.
     *
     * A message can be anything from an object to a string
     *
     * @param string $hookName The label.
     *
     * @param mixed  $message  The message.
     *
     * @return void
     */
    public function addHook($hookName, $message)
    {
        $this->messages[] = array(
            'message' => $this->getDataFormatter()->formatVar($message),
            'label' => $hookName,
            'time' => microtime(true)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $data = array();
        $seq  = array();
        foreach ($this->messages as $var)
        {
            $label = $var['label'];
            if (!isset($seq[$label]))
            {
                $seq[$label] = 0;
            }
            else
            {
                $seq[$label]++;
            }

            $label = $label .  ' ' . $seq[$label];

            $data[$label] = $var['message'];
        }

        return array(
            'hooks' => $data,
            'nb_hooks' => count($this->messages)
        );
    }

    /**
     * Retrieve the name.
     *
     * @return string
     */
    public function getName()
    {
        return 'hook-inspection';
    }

    /**
     * Retrieve the widget information.
     *
     * @return array
     */
    public function getWidgets()
    {
        return array(
            'hook-inspection'       => array(
                'icon'              => 'tags',
                'widget'            => 'PhpDebugBar.Widgets.VariableListWidget',
                'map'               => 'hook-inspection.hooks',
                'default'           => '{}'
            ),
            'hook-inspection:badge' => array
            (
                'map'               => 'hook-inspection.nb_hooks',
                'default'           => 0
            )
        );
    }
}
