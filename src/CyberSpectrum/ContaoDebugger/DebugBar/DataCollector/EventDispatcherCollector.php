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

use DebugBar\DataCollector\MessagesCollector;

/**
 * Provides a way to log messages
 */
class EventDispatcherCollector extends MessagesCollector
{
    /**
     * Create a new instance.
     */
    public function __construct()
    {
        parent::__construct('event-dispatcher');
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages()
    {
        $messages = $this->messages;

        // Sort messages by their timestamp.
        usort($messages, function($a, $b)
        {
            if ($a['time'] === $b['time'])
            {
                return 0;
            }
            return (($a['time'] < $b['time']) ? -1 : 1);
        });

        return $messages;
    }
}
