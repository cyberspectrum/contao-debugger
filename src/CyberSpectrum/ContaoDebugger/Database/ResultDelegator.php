<?php

namespace CyberSpectrum\ContaoDebugger\Database;

use Contao\Database\Result;
use Contao\Database\Statement;
use CyberSpectrum\ContaoDebugger\Debugger;

/**
 * Delegating class for database statements.
 */
class ResultDelegator extends Result
{
	/**
	 * The real result.
	 *
	 * @var Result
	 */
	protected $result;

	/**
	 * Create a new instance.
	 *
	 * @param Result $result The database result.
	 */
	public function __construct($result)
	{
		$this->result = $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __destruct()
	{
		$this->result = null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __set($strKey, $varValue)
	{
		$this->result->__set($strKey, $varValue);
	}

	/**
	 * {@inheritDoc}
	 */
	public function __isset($strKey)
	{
		return $this->result->__isset($strKey);
	}

	/**
	 * {@inheritDoc}
	 */
	public function __get($strKey)
	{
		return $this->result->__get($strKey);
	}

	/**
	 * Invoke a method call on the database result.
	 *
	 * @param string $func The name of the method to invoke.
	 *
	 * @param array  $argv The parameters to use.
	 *
	 * @return mixed|null
	 */
	public function invoke($func, $argv)
	{
		$reflection = new \ReflectionClass($this->result);
		if (!$reflection->hasMethod($func))
		{
			return null;
		}

		$method = $reflection->getMethod($func);
		$method->setAccessible(true);
		return $method->invokeArgs($this->result, $argv);
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchRow()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchAssoc()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchEach($strKey)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchAllAssoc()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetchField($intOffset = 0)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function first()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function prev()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function next()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function last()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function row($blnEnumerated = false)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	public function reset()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	// @codingStandardsIgnoreStart - We can not camel case inherited abstract methods.
	/**
	 * {@inheritDoc}
	 */
	protected function fetch_row()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fetch_assoc()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function num_rows()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function num_fields()
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function fetch_field($intOffset)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}

	/**
	 * {@inheritDoc}
	 */
	protected function data_seek($intIndex)
	{
		return $this->invoke(__FUNCTION__, func_get_args());
	}
	// @codingStandardsIgnoreEnd - We can not camel case inherited abstract methods.

	/**
	 * {@inheritDoc}
	 */
	public function free()
	{
		$this->invoke(__FUNCTION__, func_get_args());
		$this->result = null;
	}
}
