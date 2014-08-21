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

namespace CyberSpectrum\ContaoDebugger;

use ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent;
use CyberSpectrum\ContaoDebugger\Database\DatabaseDebugger;
use CyberSpectrum\ContaoDebugger\DebugBar\DebugBar;
use CyberSpectrum\ContaoDebugger\Exception\ExceptionHandler;
use CyberSpectrum\ContaoDebugger\Exception\PostMortemException;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\OpenHandler;
use DebugBar\Storage\FileStorage;

/**
 * General purpose debugger for Contao.
 */
class Debugger
{
	/**
	 * Logic flag if debug data has already been sent.
	 *
	 * @var bool
	 */
	protected static $debuggerDone = false;

	/**
	 * Try to increase the memory limit.
	 *
	 * Stolen from composer.
	 *
	 * @return void
	 */
	// @codingStandardsIgnoreStart - this method is as good as it can be.
	public static function setMemoryLimit()
	{
		if (function_exists('ini_set'))
		{
			$memoryInBytes = function ($value)
			{
				$unit  = strtolower(substr($value, -1, 1));
				$value = (int)$value;
				switch ($unit)
				{
					/** @noinspection PhpMissingBreakStatementInspection - No break (cumulative multiplier) */
					case 'g':
						$value *= 1024;
					/** @noinspection PhpMissingBreakStatementInspection - No break (cumulative multiplier) */
					case 'm':
						$value *= 1024;
					/** @noinspection PhpMissingBreakStatementInspection - No break (cumulative multiplier) */
					case 'k':
						$value *= 1024;

					default:
						// Do nothing.
				}

				return $value;
			};

			$memoryLimit = trim(ini_get('memory_limit'));
			// Increase memory_limit if it is lower than 2048M.
			if ($memoryLimit != -1 && $memoryInBytes($memoryLimit) < (2048 * 1024 * 1024))
			{
				@ini_set('memory_limit', '2048M');
			}
			unset($memoryInBytes, $memoryLimit);
		}
	}
	// @codingStandardsIgnoreEnd

	/**
	 * Create a new debugger instance.
	 *
	 * @return DebugBar
	 */
	public static function createDebuggerInstance()
	{
		$debugBar = new DebugBar();
		$debugBar->setStorage(new FileStorage(TL_ROOT . '/system/tmp/debug.log'));

		return $debugBar;
	}

	/**
	 * First stage of the debugger initialization.
	 *
	 * Called from config.php.
	 *
	 * @return Debugger
	 */
	public static function boot()
	{
		ini_set('html_errors', 0);
		ini_set('display_startup_errors', 1);
		ini_set('display_errors', 1);

		array_insert($GLOBALS['TL_HOOKS']['initializeSystem'], 0, array(array(__CLASS__, 'initializeSystem')));
		$GLOBALS['TL_HOOKS']['outputBackendTemplate'][]  = array(__CLASS__, 'handleOutput');
		$GLOBALS['TL_HOOKS']['outputFrontendTemplate'][] = array(__CLASS__, 'handleOutput');

		self::setMemoryLimit();

		if (!is_dir(TL_ROOT . '/system/tmp/debug.log'))
		{
			mkdir(TL_ROOT . '/system/tmp/debug.log', 0777, true);
		}

		$debugBar = self::createDebuggerInstance();
		$debugBar->getJavascriptRenderer()->setOpenHandlerUrl(TL_PATH . '/system/modules/debug/data/retrieve.php');

		/** @var ExceptionsCollector $exceptions */
		$exceptions = $debugBar->getCollector('exceptions');
		ExceptionHandler::attach($exceptions);

		register_shutdown_function(array(__CLASS__, 'postMortem'));

		if (function_exists('posix_getpwuid') && function_exists('posix_geteuid') && function_exists('get_current_user'))
		{
			$processUser = posix_getpwuid(posix_geteuid());
			$processUser = $processUser['name'];
			$scriptUser  = get_current_user();
			$collector   = $debugBar->getCollector('messages');
			/** @var MessagesCollector $collector */
			if ($processUser != $scriptUser)
			{
				$collector->addMessage(
					sprintf(
						'Script is executed as user "%s" but the script is owned by user %s',
						$processUser,
						$scriptUser
					),
					'warn'
				);
			}
			else
			{
				$collector
					->addMessage(sprintf('Script is executed as user %s - this matches the file owner. Good!', $processUser));
			}
		}

		return $debugBar;
	}

	/**
	 * Second stage of the debugger initialization.
	 *
	 * Called from initializeSystem HOOK.
	 *
	 * @return void
	 */
	public static function initializeSystem()
	{
		ini_set('display_startup_errors', 0);
		ini_set('display_errors', 0);
	}

	/**
	 * Mark the debugger to already have sent debug data.
	 *
	 * @return void
	 */
	protected static function markDone()
	{
		self::$debuggerDone = true;

		self::getDebugger()->stopCollectors();
	}

	/**
	 * Check if the debug information has already been sent.
	 *
	 * @return bool
	 */
	protected static function isDone()
	{
		return self::$debuggerDone;
	}

	/**
	 * Check whether the current request is an ajax request.
	 *
	 * @return bool
	 */
	protected static function isAjax()
	{
		// @codingStandardsIgnoreStart - Accessing $_GET is safe context here.
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
			|| (isset($_GET['isAjax']) && $_GET['isAjax'] == 1)
			|| isset($_SERVER['HTTP_PHP_DEBUGGER_AJAX']);
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Start to measure execution.
	 *
	 * @param string      $message The message to use for the measurement. Must also be used in stopMeasure() call.
	 *
	 * @param string|null $label   Optional label for grouping.
	 *
	 * @return void
	 */
	public static function startMeasure($message, $label = null)
	{
		/** @var TimeDataCollector $time */
		$time = self::getHandler('time');
		if ($time)
		{
			$time->startMeasure($message, $label);
		}
	}

	/**
	 * Stop to measure execution.
	 *
	 * @param string $message The message to use for the measurement. Must also be used in startMeasure() call.
	 *
	 * @return void
	 */
	public static function stopMeasure($message)
	{
		/** @var TimeDataCollector $time */
		$time = self::getHandler('time');
		if ($time)
		{
			$time->stopMeasure($message);
		}
	}

	/**
	 * Add a debug message.
	 *
	 * @param object|string $message Either an object to be logged or a string message.
	 *
	 * @param string        $label   An optional label.
	 *
	 * @return void
	 */
	public static function addDebug($message, $label = 'info')
	{
		/** @var MessagesCollector $messages */
		$messages = self::getHandler('messages');
		if ($messages)
		{
			$messages->addMessage($message, $label);
		}
	}

	/**
	 * Retrieve the debugger instance.
	 *
	 * @return DebugBar
	 */
	public static function getDebugger()
	{
		/** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugBar */
		$debugBar = $GLOBALS['debugger'];

		return $debugBar;
	}

	/**
	 * Retrieve a log sink from the singleton instance by name.
	 *
	 * @param string $name The name of the log sing.
	 *
	 * @return \DebugBar\DataCollector\DataCollectorInterface|null
	 */
	public static function getHandler($name)
	{
		$debugBar = self::getDebugger();
		if (!$debugBar)
		{
			return null;
		}

		return $debugBar[$name];
	}

	/**
	 * Generate the debug output.
	 *
	 * @return string
	 */
	protected static function generateOutput()
	{
		$debugBar = self::getDebugger();
		$debugBar->sendDataInHeaders(true);
		return $debugBar->getJavascriptRenderer()->render(!self::isAjax());
	}

	/**
	 * Retrieve the persistent data of a certain request.
	 *
	 * @return void
	 */
	public static function getPersisted()
	{
		$openHandler = new OpenHandler(self::createDebuggerInstance());
		$openHandler->handle();
	}

	/**
	 * Generate asset data and save it to the temp dir.
	 *
	 * @param string $type The asset type to generate. Either 'css' or 'js'.
	 *
	 * @return string
	 */
	public static function generateAsset($type)
	{
		$filename = '/assets/' . $type . '/contao-debug.' . $type;

		self::markDone();

		$renderer = self::getDebugger()->getJavascriptRenderer();

		$renderer
			->setIncludeVendors(false)
			->setEnableJqueryNoConflict();
		list($cssFiles, $jsFiles) = $renderer->getAssets();

		$content = '';

		switch ($type)
		{
			case 'css':
				foreach ($cssFiles as $file)
				{
					$content .= str_replace(
							array(
								'url(php-icon.png)',
								'url(icons.png)'
							),
							array(
								'url(' . TL_PATH . '/system/modules/debug/assets/logo.png)',
								'url(' . TL_PATH . '/system/modules/debug/assets/icons.png)'
							),
							file_get_contents($file)
						) . "\n";
				}

				$content .= '
div.phpdebugbar-header,
a.phpdebugbar-restore-btn {
background: #efefef url(' . TL_PATH . '/system/modules/debug/assets/logo.png) no-repeat 5px 4px;
}

/** We need to remove the stupid Contao stylect as it does NOT update. */
select.phpdebugbar-datasets-switcher,
.phpdebugbar-openhandler select {
	opacity: 1 !important;
	display: inline-block;
}

.styled_select.phpdebugbar-datasets-switcher,
.phpdebugbar-openhandler .styled_select {
	display: none;
}
';
				break;
			case 'js':
				foreach ($jsFiles as $file)
				{
					$content .= file_get_contents($file) . "\n";
				}

				$handler = TL_PATH . '/system/modules/debug/data/retrieve.php';

				$content .= '
(function(open) {
	XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
		var isNotHandler=(url.substr(0, ' . strlen($handler) . ') !== "' . $handler . '");
		if (isNotHandler) {
			this.addEventListener("readystatechange", function() {
				if ((typeof window.phpdebugbar !== "undefined") && window.phpdebugbar && (this.readyState == 4))
				{
					for (var i = 1;; i++) {
						var id = this.getResponseHeader("phpdebugbar-id-" + i);
						if (!id) {
							break;
						}

						window.phpdebugbar.loadDataSet(id, "(ajax)");
					}
					/* window.phpdebugbar.ajaxHandler.handle(this); */
				}
			}, false);
		};
		open.call(this, method, url, async, user, pass);
		if (isNotHandler) {
			this.setRequestHeader("php-debugger-ajax", "1");
		}
	};
})(XMLHttpRequest.prototype.open);
';

				break;
			default:
		}

		file_put_contents(TL_ROOT . $filename, $content);

		return TL_PATH . $filename;
	}

	/**
	 * Generate the asset urls and return them as proper head tags.
	 *
	 * @return array
	 */
	public static function generateScripts()
	{
		$scripts = array(
			'script' =>
				'<script src="' . TL_PATH . '/assets/jquery/core/' . JQUERY . '/jquery.min.js"></script>' .
				'<script src="' . self::generateAsset('js') . '"></script>'
			,
			'css'    =>
				'<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">' .
				'<link href="' . self::generateAsset('css') . '" rel="stylesheet">'
		);
		return $scripts;
	}

	/**
	 * Manipulate the Contao Template output.
	 *
	 * @param string $strBuffer The template output buffer.
	 *
	 * @return string
	 */
	public static function handleOutput($strBuffer)
	{
		self::markDone();

		if (self::isAjax())
		{
			$debugBar = self::getDebugger();
			$debugBar->stackData();

			$i = 1;
			foreach (array_keys($debugBar->getStackedData()) as $id)
			{
				header('phpdebugbar-id-' . ($i++) . ': ' . $id);
			}

			return $strBuffer;
		}

		$scripts   = self::generateScripts();
		$strBuffer = str_replace('<head>', '<head>' . $scripts['script'], $strBuffer);
		$strBuffer = str_replace('</head>', $scripts['css'] . '</head>', $strBuffer);
		$strBuffer = str_replace('</body>', self::generateOutput() . '</body>', $strBuffer);

		return $strBuffer;
	}

	/**
	 * Shutdown function of the debugger.
	 *
	 * @return void
	 */
	public static function postMortem()
	{
		if (self::isDone())
		{
			return;
		}
		self::markDone();

		// If we end up here, we were interrupted the hard way, aka fatal error bypassing the error handler.
		if (($error = error_get_last()) !== null)
		{
			$e = new PostMortemException(
				ExceptionHandler::getErrorName($error['type']) . ': ' . $error['message'],
				0,
				$error['type'],
				$error['file'],
				$error['line']
			);

			ExceptionHandler::handleException($e);
		}

		if (self::isAjax())
		{
			foreach (headers_list() as $header)
			{
				if (substr($header, 0, 17) == 'X-Ajax-Location: ')
				{
					self::handleOutput('');
					return;
				}
			}
			self::getDebugger()->stackData();
			return;
		}

		$scripts = self::generateScripts();

		echo '<html><head><base href="' . \Environment::get('base') . '" />' .
			$scripts['css'] .
			'<style>
			body {
				margin: 0;
				padding: 0;
				background-color: #393058;
				color: #fff;
			}
			.dead_meat {
				width: 300px;
				margin: 50px auto 0;
				text-align: center;
				font-size: 12px;
			}
			.dead_meat .message {
				text-align: left;
			}
			</style>' .
			'</head><body>' .
			'<div class="dead_meat">
				<img alt="" src="data:image/x-png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAAA0CAYAAAA62j4JAAAABmJLR0QA/wD' .
				'/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3QoWFzs7yWYGWgAAABl0RVh0Q29tbWVudABDcmVhdGVkIHdpdG' .
				'ggR0lNUFeBDhcAAAExSURBVGje7ZrNEoMwCITZju//yvRkazOx+QMGJ5tLLwqV/YCggXyXis+Ck31YGHnJ5gulMqo2QgF1gVbtV' .
				'+yCBFgQYKX8nWLW9i8kkAAGgAFgABiA2XWEtRvAy/RsewEJiNgHpHvggkQWwd0eWFV/aD+sUPrnMMv2mQRYtMFRdUauv7vWa7Qm' .
				'ASMEzKqzolKLHotaQQIyq9Oyef6u+CIBXup4bWKsfZGATGq0fFj2fxIwOw5HVP9eGyu+zntZA6zy0jI/Wz44C2TqAt4vL/g+gAF' .
				'gABiARwxD1kNVVJEkAdnUjf5CRQKic/eqcG2YifwoQgJk4cPIaO46ng94LgG9U1/KcZhF8EETX4oU6ME9ugOEE6CqAiBVQXQ7Le' .
				'7RSTx8HzvmfUnA539ttgfiOUERkTc7l7FgQwJaZgAAAABJRU5ErkJggg==">
				<h1>Zombie installation!</h1>
				<span class="message">
				We are sorry but Contao crashed.<br />
				This can be for various reasons:
				<ol>
				<li>There was some fatal error in processing the page (missing files etc.).</li>
				<li>PHP ran out of memory or the maximum execution runtime is exceeded.</li>
				<li>Due to some entirely other reason, maybe some Unicorn stole some classes.</li>
				</ol>
				To continue please examine the output in the debugging bar or the php error log file.
				</span>
				</div>' .
			$scripts['script'] .
			self::generateOutput() .
			'</body>';
	}
}
