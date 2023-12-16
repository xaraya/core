<?php
/**
 * Event Observer Bridges for Xaraya to forward events to a dispatcher compatible with Symfony EventDispatcher (not PSR-14)
 *
 * Xaraya -> call xarEvents::notify() -> callback to EventObserverBridge -> dispatch with EventDispatcher -> App event subscribers
 *
 * Event names to be dispatched via the EventDispatcher are structured as:
 * - xarEvents.{scope}.{event} e.g. xarEvents.user.UserLogin
 * - xarHooks.{scope}.{event} e.g. xarHooks.item.ItemCreate
 *
 * By default, any call to xarEvents::notify or xarHooks::notify can trigger an event dispatch as well, so it's up
 * to the event/hook observer bridges and your event subscribers to select which events they want to listen to.
 * An example of a test event subscriber is available in lib/xaraya/bridge/events/testers.php
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
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
 * xarHooks::notify('ItemUpdate', $args);
 *
 * // receive the event via the event dispatcher in the event subscriber
 */

namespace Xaraya\Bridge\Events;

//use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use sys;

sys::import('xaraya.events');
sys::import('xaraya.hooks');
use xarEvents;
use xarHooks;

interface ObserverBridgeInterface
{
    public static function setDispatcher(EventDispatcherInterface $dispatcher): void;
    public static function setEventList(array $eventList = []): void;
    public static function callbackEvent($info): void;
    public static function register(): void;
    public static function unregister(): void;
    public static function getEventList(): array;
    public static function getEventName(string $eventScope, string $eventType): string;
    public static function getObservedEvents(): array;
}

class EventObserverBridge implements ObserverBridgeInterface
{
    protected static $eventNamePrefix = 'xarEvents';
    protected static $observedEvents = [];
    protected static $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher, array $eventList = [])
    {
        static::setDispatcher($dispatcher);
        static::setEventList($eventList);
    }

    public static function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        static::$dispatcher = $dispatcher;
    }

    public static function setEventList(array $eventList = []): void
    {
        if (empty($eventList)) {
            static::getObservedEvents();
        } else {
            foreach (static::getEventList() as $event => $info) {
                if (in_array($event, $eventList)) {
                    $eventName = static::getEventName($info['scope'], $event);
                    static::$observedEvents[$event] = $eventName;
                }
            }
        }
        // register all observed events here?
        static::register();
    }

    public static function callbackEvent($info, $context = null): void
    {
        if (empty(static::$dispatcher)) {
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
        (static::$dispatcher)->dispatch($tosend, static::$observedEvents[$event]);
    }

    public static function register(): void
    {
        foreach (static::getObservedEvents() as $event => $eventName) {
            xarEvents::registerCallback($event, [static::class, 'callbackEvent']);
        }
    }

    public static function unregister(): void
    {
        foreach (static::getObservedEvents() as $event => $eventName) {
            // @todo
        }
    }

    public static function getEventList(): array
    {
        return xarEvents::getSubjects();
    }

    public static function getEventName(string $eventScope, string $eventType): string
    {
        //xarEvents.scope.event
        $eventName = implode('.', [static::$eventNamePrefix, $eventScope, $eventType]);
        return $eventName;
    }

    public static function getObservedEvents(): array
    {
        //return [
        //    'event' => 'xarEvents.scope.event',
        //];
        if (!empty(static::$observedEvents)) {
            return static::$observedEvents;
        }
        $eventList = static::getEventList();
        foreach ($eventList as $event => $info) {
            $eventName = static::getEventName($info['scope'], $event);
            static::$observedEvents[$event] = $eventName;
        }
        return static::$observedEvents;
    }
}

class HookObserverBridge extends EventObserverBridge implements ObserverBridgeInterface
{
    protected static $eventNamePrefix = 'xarHooks';
    protected static $observedEvents = [];
    protected static $dispatcher;

    public static function register(): void
    {
        foreach (static::getObservedEvents() as $event => $eventName) {
            xarHooks::registerCallback($event, [static::class, 'callbackEvent']);
        }
    }

    public static function unregister(): void
    {
        foreach (static::getObservedEvents() as $event => $eventName) {
            // @todo
        }
    }

    public static function getEventList(): array
    {
        return xarHooks::getSubjects();
    }
}
