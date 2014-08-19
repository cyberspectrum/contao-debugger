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

namespace CyberSpectrum\ContaoDebugger\Exception;

use DebugBar\DataCollector\ExceptionsCollector;

/**
 * Error and exception handler of the debugger.
 *
 * @package CyberSpectrum\ContaoDebugger\Exception
 */
class ExceptionHandler
{
	/**
	 * The known error levels and their human readable representation.
	 *
	 * @var array
	 */
	public static $arrErrors = array
	(
		E_ERROR             => 'Fatal error',
		E_WARNING           => 'Warning',
		E_PARSE             => 'Parsing error',
		E_NOTICE            => 'Notice',
		E_CORE_ERROR        => 'Core error',
		E_CORE_WARNING      => 'Core warning',
		E_COMPILE_ERROR     => 'Compile error',
		E_COMPILE_WARNING   => 'Compile warning',
		E_USER_ERROR        => 'Fatal error',
		E_USER_WARNING      => 'Warning',
		E_USER_NOTICE       => 'Notice',
		E_STRICT            => 'Runtime notice',
		4096                => 'Recoverable error',
		8192                => 'Deprecated notice',
		16384               => 'Deprecated notice'
	);

	/**
	 * The attached collector.
	 *
	 * @var ExceptionsCollector
	 */
	protected static $collector;

	/**
	 * Add an exception to the collector.
	 *
	 * @param \Exception $e The exception.
	 *
	 * @return void
	 */
	protected static function addException($e)
	{
		self::$collector->addException($e);
	}

	/**
	 * Error handler.
	 *
	 * Handle errors like PHP does it natively but additionally log them to the
	 * application error log file.
	 *
	 * @param int    $intType    The type of the error.
	 *
	 * @param string $strMessage The error message.
	 *
	 * @param string $strFile    The file where the error originated from.
	 *
	 * @param int    $intLine    The line on which the error was raised.
	 *
	 * @return void
	 */
	public static function errorHandler($intType, $strMessage, $strFile, $intLine)
	{
		if (($intType === E_NOTICE) && (
				(strpos($strFile, 'system/modules/core') !== false)
				|| (strpos($strFile, 'system/helper/functions.php') !== false)
			))
		{
			return;
		}

		if (($intType != E_WARNING)
			&& (strpos($strMessage, 'sort(): Array was modified by the user comparison function') !== false)
		)
		{
			// @codingStandardsIgnoreStart
			// See:
			//   http://stackoverflow.com/questions/3235387/usort-array-was-modified-by-the-user-comparison-function
			//   https://bugs.php.net/bug.php?id=50688
			//   http://wpdailydose.com/discoveries/array-modified/
			// @codingStandardsIgnoreEnd
			return;
		}

		$e = new \ErrorException(
			self::$arrErrors[$intType] . ': ' . $strMessage,
			0,
			$intType,
			$strFile,
			$intLine
		);

		if ($intType !== E_NOTICE)
		{
			// Log the error.
			error_log(sprintf("\nPHP %s: %s in %s on line %s\n%s\n",
				self::$arrErrors[$intType],
				$strMessage,
				$strFile,
				$intLine,
				$e->getTraceAsString()));
		}

		self::addException($e);

		// Exit on severe errors.
		if (($intType & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
		{
			// Help message will get shown by debugger post mortem.
			exit;
		}
	}

	/**
	 * Exception handler.
	 *
	 * Log exceptions in the application log file and print them to the screen
	 * if "display_errors" is set. Callback to a custom exception handler defined
	 * in the application file "config/error.php".
	 *
	 * @param \Exception $e The exception.
	 *
	 * @return void
	 */
	public static function handleException($e)
	{
		error_log(sprintf("PHP Fatal error: Uncaught exception '%s' with message '%s' thrown in %s on line %s\n%s",
			get_class($e),
			$e->getMessage(),
			$e->getFile(),
			$e->getLine(),
			$e->getTraceAsString()));

		self::addException($e);
	}

	/**
	 *  Attach to the given exception collector and register the handler methods in PHP.
	 *
	 * @param ExceptionsCollector $collector The collector to attach to.
	 *
	 * @return void
	 */
	public static function attach(ExceptionsCollector $collector)
	{
		self::$collector = $collector;
		set_error_handler(array(__CLASS__, 'errorHandler'), E_ALL);
		set_exception_handler(array(__CLASS__, 'handleException'));
	}
}
