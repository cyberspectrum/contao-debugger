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

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Collects info about the request duration as well as providing
 * a way to log duration of any operations
 */
class BenchmarkCollector extends DataCollector implements Renderable
{
    /**
     * Keep track of the time.
     *
     * @var int
     */
    protected $time = 0;

    /**
     * List of methods.
     *
     * @var array
     */
    protected $measures = array();

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->time = microtime(true);
        $this->startProfiling();
    }

    /**
     * Start the profiling.
     *
     * @return void
     */
    public function startProfiling()
    {
        declare(ticks = 1);
        register_tick_function(array($this, 'profiling'));
    }

    /**
     * Stop profiling.
     *
     * @return void
     */
    public function stopProfiling()
    {
        unregister_tick_function(array($this, 'profiling'));
    }

    /**
     * Tick function for profiling.
     *
     * @return void
     */
    public function profiling()
    {
        $e        = new \Exception();
        $trace    = $e->getTrace();
        $class    = isset($trace[1]['class']) ? $trace[1]['class'] : null;
        $function = $trace[1]['function'];
        $method   = ($class ? $class . '::' : '') . $function;

        if (!isset($this->measures[$method]))
        {
            $this->measures[$method] = 0;
        }

        $time = microtime(true);

        $this->measures[$method] += ($time - $this->time);
        $this->time               = $time;
    }

    /**
     * Returns an array of all measures.
     *
     * @return array
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $measures = array();
        $values   = $this->measures;
        $max      = 0;
        $overhead = 0;

        arsort($values);
        foreach ($values as $method => $measure)
        {
            if ((substr($method, 0, strlen('CyberSpectrum\ContaoDebugger')) == 'CyberSpectrum\ContaoDebugger')
            || (substr($method, 0, strlen('SqlFormatter')) == 'SqlFormatter')
            || (substr($method, 0, strlen('DebugBar')) == 'DebugBar')
            )
            {
                $overhead += $measure;
                continue;
            }

            $measures[] = array(
                'label'          => $method,
                'start'          => 0,
                'relative_start' => 0,
                'end'            => $measure,
                'relative_end'   => $measure,
                'duration'       => $measure,
                'duration_str'   => $this->getDataFormatter()->formatDuration($measure)
            );

            if ($max < $measure)
            {
                $max = $measure;
            }
        }

        return array(
            'duration'     => $max,
            'overhead'     => 'Overhead: ' . $this->getDataFormatter()->formatDuration($overhead),
            'measures'     => array_values($measures)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'benchmark';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            'benchmark'   => array(
                'icon'    => 'tasks',
                'tooltip' => 'Benchmarking information',
                'widget'  => 'PhpDebugBar.Widgets.TimelineWidget',
                'map'     => 'benchmark',
                'default' => '{}'
            ),
            'benchmark:badge' => array(
                'map'        => 'benchmark.overhead',
                'default'    => 'null'
            )
        );
    }
}
