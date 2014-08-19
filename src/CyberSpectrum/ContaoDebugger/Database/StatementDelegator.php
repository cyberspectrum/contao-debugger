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

use Contao\Database\Statement;
use CyberSpectrum\ContaoDebugger\Debugger;
use Database\Result;

/**
 * Delegating class for database statements.
 */
class StatementDelegator extends Statement
{
	/**
	 * The real statement.
	 *
	 * @var Statement
	 */
	protected $statement;

	/**
	 * The database.
	 *
	 * @var DatabaseDelegator
	 */
	protected $database;

	/**
	 * The real query, before parameter replacement has been done.
	 *
	 * @var string
	 */
	protected $realQuery = '';

	/**
	 * Optional query parameters.
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * Start time.
	 *
	 * @var float
	 */
	protected $timeStart;

	/**
	 * End time.
	 *
	 * @var float
	 */
	protected $timeStop;

	/**
	 * Create a new instance.
	 *
	 * @param Statement         $statement The statement to wrap.
	 *
	 * @param DatabaseDelegator $database  The database.
	 */
	public function __construct($statement, DatabaseDelegator $database)
	{
		$this->statement = $statement;
		$this->database  = $database;
	}

	/**
	 * Magic getter.
	 *
	 * @param string $key The key to retrieve.
	 *
	 * @return mixed|null
	 */
	public function __get($key)
	{
		$result = $this->statement->$key;

		if ($result === null)
		{
			$result = $this->reflectionProperty($key);
		}

		return $result;
	}

	/**
	 * Invoke a method call on the database statement.
	 *
	 * @param string $func The name of the method to invoke.
	 *
	 * @param array  $argv The parameters to use.
	 *
	 * @return mixed|null
	 */
	public function invoke($func, $argv)
	{
		$reflection = new \ReflectionClass($this->statement);
		if (!$reflection->hasMethod($func))
		{
			return null;
		}

		$method = $reflection->getMethod($func);
		$method->setAccessible(true);
		return $method->invokeArgs($this->statement, $argv);
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
		$reflection = new \ReflectionClass($this->statement);
		if (!$reflection->hasProperty($name))
		{
			return null;
		}

		$property = $reflection->getProperty($name);
		$property->setAccessible(true);
		return $property->getValue($this->statement);
	}

	/**
	 * Wrap a result object if needed.
	 *
	 * @param mixed $result The result to be wrapped.
	 *
	 * @return ResultDelegator|mixed
	 */
	protected function wrapResult($result)
	{
		if ($result instanceof Result)
		{
			$result = new ResultDelegator($result);
		}
		elseif ($result === $this->statement)
		{
			$result = $this;
		}

		return $result;
	}

	/**
	 * Pass the given statement or result to the debugger.
	 *
	 * @param null|ResultDelegator $result The result (optional).
	 *
	 * @return void
	 */
	protected function passToDebugger($result = null)
	{
		$arrData['query']     = $this->statement->strQuery;
		$arrData['realquery'] = $this->realQuery;
		$arrData['params']    = $this->parameters;
		$arrData['timeStart'] = $this->timeStart;
		$arrData['timeStop']  = $this->timeStop;
		$arrData['duration']  = ($this->timeStop - $this->timeStart);

		if ($result === null || strncasecmp($this->statement->strQuery, 'SELECT', 6) !== 0)
		{
			if (strncasecmp($this->statement->strQuery, 'SHOW', 4) === 0)
			{
				$arrData['return_count'] = $this->statement->affectedRows;
				$arrData['returned']     = sprintf('%s row(s) returned', $this->statement->affectedRows);
			}
			else
			{
				$arrData['affected_count'] = $this->affectedRows;
				$arrData['affected']       = sprintf('%d row(s) affected', $this->statement->affectedRows);
			}
		}
		else
		{
			if (($arrExplain = $this->statement->explain()) != false)
			{
				$arrData['explain'] = $arrExplain;
			}

			$arrData['return_count'] = $result->numRows;
			$arrData['returned']     = sprintf('%s row(s) returned', $result->numRows);
		}

		$this->database->addStatement($arrData);
	}

	/**
	 * {@inheritDoc}
	 */
	public function prepare($strQuery)
	{
		$this->realQuery = $strQuery;
		return $this->wrapResult($this->invoke(__FUNCTION__, func_get_args()));
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($arrParams)
	{
		return $this->wrapResult($this->invoke(__FUNCTION__, func_get_args()));
	}

	/**
	 * {@inheritDoc}
	 */
	public function limit($intRows, $intOffset = 0)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function execute()
	{
		$this->parameters = func_get_args();
		$this->timeStart  = microtime(true);
		$result           = $this->wrapResult($this->invoke(__FUNCTION__, func_get_args()));
		$this->timeStop   = microtime(true);

		$this->passToDebugger($result);

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function query($strQuery = '')
	{
		if (!$this->realQuery)
		{
			$this->realQuery = $strQuery;
		}

		$this->timeStart = microtime(true);
		$result          = $this->wrapResult($this->invoke(__FUNCTION__, func_get_args()));
		$this->timeStop  = microtime(true);

		$this->passToDebugger($result);

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function replaceWildcards($arrValues)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function escapeParams($arrValues)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function debugQuery($objResult = null)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function explain()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	// @codingStandardsIgnoreStart - We can not camel case inherited abstract methods.
	/**
	 * {@inheritDoc}
	 */
	protected function prepare_query($strQuery)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function string_escape($strString)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function limit_query($intRows, $intOffset)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute_query()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_error()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function affected_rows()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function insert_id()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function explain_query()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function createResult($resResult, $strQuery)
	{
		return new ResultDelegator($this->invoke(__FUNCTION__, func_get_args()));
	}
	// @codingStandardsIgnoreEnd - We can not camel case inherited abstract methods.
}
