<?php

/**
 * Event Observer Bridges for Xaraya to forward events to a dispatcher compatible with Symfony EventDispatcher (not PSR-14)
 *
 * Xaraya -> call xar::events()->notify() -> callback to EventObserverBridge -> dispatch with EventDispatcher -> App event subscribers
 *
 * Event names to be dispatched via the EventDispatcher are structured as:
 * - xarEvents.{scope}.{event} e.g. xarEvents.user.UserLogin
 * - xarHooks.{scope}.{event} e.g. xarHooks.item.ItemCreate
 *
 * By default, any call to xar::events()->notify or xar::hooked()->notify can trigger an event dispatch as well, so it's up
 * to the event/hook observer bridges and your event subscribers to select which events they want to listen to.
 * An example of a test event subscriber is available in lib/xaraya/bridge/events/testers.php
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * use Xaraya\Services\xar;
 * sys::init();
 * xar::load(xarCore::SYSTEM_USER);
 *
 * use Symfony\Component\EventDispatcher\EventDispatcher;
 * use Symfony\Component\EventDispatcher\EventSubscriberInterface;
 * use Xaraya\Bridge\Events\EventObserverBridge;
 * use Xaraya\Bridge\Events\HookObserverBridge;
 * use Xaraya\Bridge\Events\TestObserverBridgeSubscriber;
 *
 * // get the event dispatcher we're going to bridge events to
 * $dispatcher = new EventDispatcher();
 * // set up the event observer bridge to dispatch a few events
 * $eventbridge = new EventObserverBridge($dispatcher, ['Event']);
 * // set up the hook observer bridge to dispatch a few hooks
 * $hookbridge = new HookObserverBridge($dispatcher, ['ItemUpdate']);
 *
 * // have an event subscriber show interest in a few events and/or hooks - see testers.php
 * $eventList = array_keys($eventbridge->getObservedEvents());
 * $hookList = array_keys($hookbridge->getObservedEvents());
 * $subscriber = new TestObserverBridgeSubscriber($eventList, $hookList);
 * // and add it to the event dispatcher to see something happen
 * $dispatcher->addSubscriber($subscriber);
 *
 * // trigger an event or hook call in Xaraya
 * $itemid = spl_object_id($subscriber);
 * $args = ['module' => 'dynamicdata', 'itemtype' => 3, 'itemid' => $itemid];
 * xar::hooked()->notify('ItemUpdate', $args, $context);
 *
 * // receive the event via the event dispatcher in the event subscriber
 */

namespace Xaraya\Bridge\Events;

//use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Xaraya\Services\WithServicesClass;

interface ObserverBridgeInterface
{
    public function setDispatcher(EventDispatcherInterface $dispatcher): void;
    public function setEventList(array $eventList = []): void;
    public function callbackEvent($info, $context = null): void;
    public function register(): void;
    public function unregister(): void;
    public function getEventList(): array;
    public static function getEventName(string $eventScope, string $eventType): string;
    public static function getObservedEvents(): array;
}

class EventObserverBridge implements ObserverBridgeInterface
{
    use WithServicesClass;

    protected static $eventNamePrefix = 'xarEvents';
    protected static $observedEvents = [];
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher, array $eventList = [], $xar = null)
    {
        $this->setServicesClass($xar);
        $this->setDispatcher($dispatcher);
        $this->setEventList($eventList);
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setEventList(array $eventList = []): void
    {
        if (empty($eventList)) {
            foreach ($this->getEventList() as $event => $info) {
                $eventName = static::getEventName($info['scope'], $event);
                static::$observedEvents[$event] = $eventName;
            }
        } else {
            foreach ($this->getEventList() as $event => $info) {
                if (in_array($event, $eventList)) {
                    $eventName = static::getEventName($info['scope'], $event);
                    static::$observedEvents[$event] = $eventName;
                }
            }
        }
        // register all observed events here?
        $this->register();
    }

    public function callbackEvent($info, $context = null): void
    {
        if (empty($this->dispatcher)) {
            return;
        }
        $event = $info['event'] ?? 'Event';
        //echo "Got event $event\n";
        if (!array_key_exists($event, static::$observedEvents)) {
            return;
        }
        // observers obtain arguments from the subject
        $tosend = new DefaultEvent($info);
        // set context if available in callback
        $tosend->setContext($context);
        // observers may, or may not return a response, but EventDispatcher doesn't anyway
        $this->dispatcher->dispatch($tosend, static::$observedEvents[$event]);
    }

    public function register(): void
    {
        $xar = $this->getServicesClass();
        foreach (static::getObservedEvents() as $event => $eventName) {
            $xar->events()->registerCallback($event, [$this, 'callbackEvent']);
        }
    }

    public function unregister(): void
    {
        $xar = $this->getServicesClass();
        foreach (static::getObservedEvents() as $event => $eventName) {
            // @todo
        }
    }

    public function getEventList(): array
    {
        $xar = $this->getServicesClass();
        return $xar->events()->getSubjects();
    }

    public static function getEventName(string $eventScope, string $eventType): string
    {
        //xarEvents.scope.event or xarHooks.scope.event
        $eventName = implode('.', [static::$eventNamePrefix, $eventScope, $eventType]);
        return $eventName;
    }

    /**
     * Use static method here by analogy with EventSubscriberInterface::getSubscribedEvents()
     * @return array<string, string>
     */
    public static function getObservedEvents(): array
    {
        //return [
        //    'event' => 'xarEvents.scope.event',
        //];
        return static::$observedEvents;
    }
}

class HookObserverBridge extends EventObserverBridge implements ObserverBridgeInterface
{
    protected static $eventNamePrefix = 'xarHooks';
    protected static $observedEvents = [];
    protected $dispatcher;

    public function register(): void
    {
        $xar = $this->getServicesClass();
        foreach (static::getObservedEvents() as $event => $eventName) {
            $xar->hooked()->registerCallback($event, [$this, 'callbackEvent']);
        }
    }

    public function unregister(): void
    {
        $xar = $this->getServicesClass();
        foreach (static::getObservedEvents() as $event => $eventName) {
            // @todo
        }
    }

    public function getEventList(): array
    {
        $xar = $this->getServicesClass();
        return $xar->hooked()->getSubjects();
    }
}
