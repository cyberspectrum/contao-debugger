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

namespace CyberSpectrum\ContaoDebugger\Events;

use ContaoCommunityAlliance\Contao\EventDispatcher\Event\CreateEventDispatcherEvent;
use CyberSpectrum\ContaoDebugger\DebugBar\DataCollector\EventDispatcherCollector;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Debugging event dispatcher to wrap the real event dispatcher.
 */
class DebuggedEventDispatcher implements EventDispatcherInterface
{
	/**
	 * The event dispatcher.
	 *
	 * @var EventDispatcherInterface
	 */
	protected $realDispatcher;

	/**
	 * The collector to use.
	 *
	 * @var EventDispatcherCollector
	 */
	protected $collector;

	/**
	 * Register the event dispatcher and wrap the given event dispatcher.
	 *
	 * @param CreateEventDispatcherEvent $event The event.
	 *
	 * @return void
	 */
	public static function register(CreateEventDispatcherEvent $event)
	{
		$collector  = new EventDispatcherCollector();
		$dispatcher = new DebuggedEventDispatcher($event->getEventDispatcher(), $collector);
		$event->setEventDispatcher($dispatcher);

		/** @var \CyberSpectrum\ContaoDebugger\DebugBar\DebugBar $debugBar */
		$debugBar = $GLOBALS['debugger'];
		$debugBar->addCollector($collector);
	}

	/**
	 * Create a new instance.
	 *
	 * @param EventDispatcherInterface $realDispatcher The real event dispatcher to be wrapped.
	 *
	 * @param EventDispatcherCollector $collector      The collector to use.
	 */
	public function __construct(EventDispatcherInterface $realDispatcher, EventDispatcherCollector $collector)
	{
		$this->realDispatcher = $realDispatcher;
		$this->collector      = $collector;
	}

	/**
	 * Add a debug message to the logger.
	 *
	 * @param string $message The message to log.
	 *
	 * @param string $label   The label under which to file this message.
	 *
	 * @return void
	 */
	protected function debug($message, $label)
	{
		$this->collector->addMessage($message, $label);
	}

	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @param string $eventName The name of the event to dispatch. The name of
	 *                          the event is the name of the method that is
	 *                          invoked on listeners.
	 *
	 * @param Event  $event     The event to pass to the event handlers/listeners.
	 *                          If not supplied, an empty Event instance is created.
	 *
	 * @return Event
	 *
	 * @api
	 */
	public function dispatch($eventName, Event $event = null)
	{
		$listeners = $this->getListeners($eventName);
		if ($listeners)
		{
			$this->debug(sprintf(
				'Firing event %s(%s) to %s subscribers.',
				$eventName,
				get_class($event),
				count($listeners)
				), 'fire-used');
		}
		return $this->realDispatcher->dispatch($eventName, $event);
	}

	/**
	 * Adds an event listener that listens on the specified events.
	 *
	 * @param string   $eventName The event to listen on.
	 *
	 * @param callable $listener  The listener.
	 *
	 * @param integer  $priority  The higher this value, the earlier an event listener will be triggered in the chain
	 *                            (defaults to 0).
	 *
	 * @api
	 *
	 * @return void
	 */
	public function addListener($eventName, $listener, $priority = 0)
	{
		$this->realDispatcher->addListener($eventName, $listener, $priority);
	}

	/**
	 * Adds an event subscriber.
	 *
	 * The subscriber is asked for all the events he is
	 * interested in and added as a listener for these events.
	 *
	 * @param EventSubscriberInterface $subscriber The subscriber.
	 *
	 * @api
	 *
	 * @return void
	 */
	public function addSubscriber(EventSubscriberInterface $subscriber)
	{
		$this->realDispatcher->addSubscriber($subscriber);

	}

	/**
	 * Removes an event listener from the specified events.
	 *
	 * @param string|array $eventName The event(s) to remove a listener from.
	 *
	 * @param callable     $listener  The listener to remove.
	 *
	 * @return void
	 */
	public function removeListener($eventName, $listener)
	{
		$this->realDispatcher->removeListener($eventName, $listener);

	}

	/**
	 * Removes an event subscriber.
	 *
	 * @param EventSubscriberInterface $subscriber The subscriber.
	 *
	 * @return void
	 */
	public function removeSubscriber(EventSubscriberInterface $subscriber)
	{
		$this->realDispatcher->removeSubscriber($subscriber);

	}

	/**
	 * Gets the listeners of a specific event or all listeners.
	 *
	 * @param string $eventName The name of the event.
	 *
	 * @return array The event listeners for the specified event, or all event listeners by event name
	 */
	public function getListeners($eventName = null)
	{
		return $this->realDispatcher->getListeners($eventName);
	}

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @param string $eventName The name of the event.
	 *
	 * @return Boolean true if the specified event has any listeners, false otherwise
	 */
	public function hasListeners($eventName = null)
	{
		return $this->realDispatcher->hasListeners($eventName);
	}
}

