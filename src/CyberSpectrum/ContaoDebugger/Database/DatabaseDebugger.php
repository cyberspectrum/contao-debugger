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

namespace CyberSpectrum\ContaoDebugger\Database;

use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\ContaoSQLCollector;

/**
 * Debugger delegator for Contao Database.
 */
class DatabaseDebugger extends \ArrayObject
{
    /**
     * The database collector in use.
     *
     * @var ContaoSQLCollector
     */
    protected $collector = null;

    /**
     * Attach the debugger to the Contao Database.
     *
     * @param ContaoSQLCollector $collector The SQL collector to use.
     *
     * @return void
     */
    public static function attach(ContaoSQLCollector $collector = null)
    {
        static $attached;

        if (isset($attached)) {
            return;
        }

        $watcher = new self($collector);

        $reflection = new \ReflectionClass('\Database');
        $property   = $reflection->getProperty('arrInstances');
        $property->setAccessible(true);
        $property->setValue($watcher);

        $reflection = new \ReflectionClass('\Contao\Database');
        $property   = $reflection->getProperty('arrInstances');
        $property->setAccessible(true);
        $property->setValue($watcher);

        $attached = true;
    }

    /**
     * Log a statement.
     *
     * @param array $info The information.
     *
     * @return void
     */
    public function addStatement($info)
    {
        if ($this->collector) {
            $this->collector->addStatement($info);
        }
    }

    /**
     * Create a new instance.
     *
     * @param ContaoSQLCollector $collector The SQL collector to use.
     */
    public function __construct(ContaoSQLCollector $collector = null)
    {
        parent::__construct(array(), (self::STD_PROP_LIST | self::ARRAY_AS_PROPS));
        $this->collector = $collector;
    }

    /**
     * Get an offset in the array object.
     *
     * @param string $index The hash key of the database to be fetched.
     *
     * @return mixed
     */
    public function offsetGet($index)
    {
        $val = parent::offsetGet($index);
        return $val;
    }

    /**
     * Set an offset in the array object.
     *
     * @param string    $index  The hash key of the database to be set.
     *
     * @param \Database $newVal The new Database instance.
     *
     * @return void
     */
    public function offsetSet($index, $newVal)
    {
        $val = new DatabaseDelegator($newVal, $this);

        parent::offsetSet($index, $val);
    }

    /**
     * Returns whether the requested database connection exists.
     *
     * @param string $index The hash key of the database to be checked.
     *
     * @return bool true if the requested database exists, otherwise false.
     */
    public function offsetExists($index)
    {
        $val = parent::offsetExists($index);
        return $val;
    }

    /**
     * Unregister the database with the given hash key.
     *
     * @param string $index The hash key of the database to be checked.
     *
     * @return void
     */
    // @codingStandardsIgnoreStart - The override is not useless, we need it for the phpDoc.
    public function offsetUnset($index)
    {
        parent::offsetUnset($index);
    }
    // @codingStandardsIgnoreEnd
}
