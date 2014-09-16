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

use Contao\Database;
use Contao\Database\Statement;
use CyberSpectrum\ContaoDebugger\Debugger;

/**
 * Delegator class to intercept calls to the database and log statistics.
 */
class DatabaseDelegator extends Database
{
    /**
     * The database to which calls are being delegated.
     *
     * @var Database
     */
    protected $database;

    /**
     * The debugger we are attached to.
     *
     * @var DatabaseDebugger
     */
    protected $debugger;

    /**
     * Create a new instance.
     *
     * @param Database         $database The database to use for delegation.
     *
     * @param DatabaseDebugger $debugger The database debugger.
     */
    public function __construct($database, DatabaseDebugger $debugger)
    {
        $this->database             = $database;
        $this->debugger             = $debugger;
        $this->resConnection        = $this->reflectionProperty('resConnection');
        $this->blnDisableAutocommit = $this->reflectionProperty('blnDisableAutocommit');
        $this->arrConfig            = $this->reflectionProperty('arrConfig');
    }

    /**
     * Close the database connection if it is not permanent.
     */
    public function __destruct()
    {
        $this->database = null;
    }

    /**
     * Magic getter.
     *
     * @param mixed $key The key to be retrieved from the database.
     *
     * @return null|string
     */
    public function __get($key)
    {
        $result = $this->getDatabase()->$key;

        if ($result === null) {
            $result = $this->reflectionProperty($key);
        }

        return $result;
    }

    /**
     * Retrieve the real database instance.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Add statement to the debugger.
     *
     * @param array $info The debug information.
     *
     * @return void
     */
    public function addStatement($info)
    {
        $this->debugger->addStatement($info);
    }

    /**
     * Invoke a method call on the database instance.
     *
     * @param string $func The name of the method to invoke.
     *
     * @param array  $argv The parameters to use.
     *
     * @return mixed|null
     */
    public function invoke($func, $argv)
    {
        $reflection = new \ReflectionClass($this->getDatabase());
        if (!$reflection->hasMethod($func)) {
            return null;
        }

        $method = $reflection->getMethod($func);
        $method->setAccessible(true);
        return $method->invokeArgs($this->getDatabase(), $argv);
    }

    /**
     * Retrieve the given property via reflection from the database.
     *
     * @param string $name The name of the property.
     *
     * @return mixed
     */
    protected function reflectionProperty($name)
    {
        $reflection = new \ReflectionClass($this->getDatabase());
        if (!$reflection->hasProperty($name)) {
            return null;
        }

        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($this->getDatabase());
    }

    /**
     * Magic method caller.
     *
     * @param string $func The name of the method to be called.
     *
     * @param array  $argv The parameters to use.
     *
     * @return $this|StatementDelegator|mixed|null
     */
    public function __call($func, $argv)
    {
        if ($func == 'execute') {
            return $this->__call('prepare', $argv)->__call($func, array());
        }

        if ($func == 'prepare') {
            $statement = new StatementDelegator($this->invoke('createStatement', array(
                $this->reflectionProperty('resConnection'),
                $this->reflectionProperty('blnDisableAutocommit')
            )), $this);

            $statement->invoke('prepare', $argv);

            return $statement;
        }

        $result = $this->invoke($func, $argv);

        if ($result === $this->getDatabase()) {
            return $this;
        }

        if ($result instanceof Statement) {
            Debugger::addDebug('\Contao\Database\Statement::' . $func . ' wrap()');
            return new StatementDelegator($result, $this);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function connect()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function disconnect()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    // @codingStandardsIgnoreStart - We can not camel case inherited abstract methods.
    /**
     * {@inheritDoc}
     */
    protected function get_error()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function find_in_set($strKey, $varSet, $blnIsField = false)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function list_fields($strTable)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function set_database($strDatabase)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function begin_transaction()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function commit_transaction()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function rollback_transaction()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function lock_tables($arrTables)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function unlock_tables()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function get_size_of($strTable)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function get_next_id($strTable)
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritDoc}
     */
    protected function get_uuid()
    {
        return $this->invoke(__FUNCTION__, func_get_args());
    }
    // @codingStandardsIgnoreEnd - We can not camel case inherited abstract methods.

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // @codingStandardsIgnoreStart - ignore unused parameter.
    protected function createStatement($resConnection, $blnDisableAutocommit)
    {
        return new StatementDelegator($this->invoke(__FUNCTION__, array(
            $this->reflectionProperty('resConnection'),
            $this->reflectionProperty('blnDisableAutocommit')
        )), $this);
    }
    // @codingStandardsIgnoreEnd
}
