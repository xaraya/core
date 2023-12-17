<?php
/**
 * Event Subscribers compatible with Symfony EventDispatcher (not PSR-14) to notify xarEvents or xarHooks
 *
 * App -> dispatch event with EventDispatcher -> receive with EventSubscriber -> call xarEvents::notify() -> Xaraya event observers
 *
 * Event names to be dispatched via the EventDispatcher are structured as:
 * - xarEvents.{scope}.{event} e.g. xarEvents.user.UserLogin
 * - xarHooks.{scope}.{event} e.g. xarHooks.item.ItemCreate
 *
 * By default, events dispatched via the EventDispatcher will trigger a xarEvents::notify or xarHooks::notify
 * call in Xaraya. Any event/hook observers listening for that event are configured in Xaraya as before.
 * The EventDispatcher does not return any results, but the response to an event is available via the subscriber.
 * Optionally, specific callback functions can also be defined to react to certain events (see also listeners).
 *
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * use Symfony\Component\EventDispatcher\EventDispatcher;
 * //use Xaraya\Bridge\Events\EventSubscriber;
 * use Xaraya\Bridge\Events\HookSubscriber;
 * use Xaraya\Bridge\Events\DefaultEvent;
 * use Xaraya\Structures\Context;
 *
 * // subscriber bridge for events and/or hooks in your app
 * //$subscriber = new EventSubscriber();
 * $subscriber = new HookSubscriber();
 * //$eventlist = $subscriber::getSubscribedEvents();
 * //echo var_export($eventlist, true);
 * $dispatcher = new EventDispatcher();
 * $dispatcher->addSubscriber($subscriber);
 *
 * // current context
 * $context = new Context(['requestId' => 'something']);
 * // create an event with $subject corresponding to the $args in xarEvents::notify()
 * $subject = ['module' => 'dynamicdata', 'itemtype' => 3, 'itemid' => 123];
 * $event = new DefaultEvent($subject);
 * // set context if available
 * $event->setContext($context);
 * // this will call xarHooks::notify('ItemCreate', $subject) and save any response in the subscriber
 * $dispatcher->dispatch($event, 'xarHooks.item.ItemCreate');
 * $responses = $subscriber->getResponses();
 */

namespace Xaraya\Bridge\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Exception;
use sys;

sys::import('xaraya.events');
sys::import('xaraya.hooks');
use xarEvents;
use xarHooks;

class EventSubscriber implements EventSubscriberInterface
{
    protected static $eventNamePrefix = 'xarEvents';
    protected static $subscribedEvents = [];
    // could be used to extend with other methods like workflow
    protected static $eventTypeMethods = [
        'scope' => [
            'event' => 'onScopeEvent',
        ],
    ];
    protected $responses = [];

    public function onScopeEvent($event, string $eventName = '')
    {
        //echo static::class . " onScopeEvent got $eventName = " . var_export($event, true) . "\n";
        $subject = $event->getSubject();
        [$prefix, $scope, $type] = explode('.', $eventName);
        $context = $event->getContext();
        $response = $this->notify($type, $subject, $context);
        //echo "Response: " . var_export($response, true);
        $this->responses[$eventName] = $response;
    }

    public function notify($type, $subject, $context = null)
    {
        $response = xarEvents::notify($type, $subject, $context);
        return $response;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public static function getEventName(string $eventScope, string $eventType)
    {
        //xarEvents.scope.event
        $eventName = implode('.', [static::$eventNamePrefix, $eventScope, $eventType]);
        return $eventName;
    }

    public static function addSubscribedEvent(string $eventScope, string $eventType)
    {
        $eventName = static::getEventName($eventScope, $eventType);
        //static::$subscribedEvents[$eventName] = [static::$eventTypeMethods[$eventScope][$eventType]];
        static::$subscribedEvents[$eventName] = 'onScopeEvent';
        return $eventName;
    }

    public static function getEventList()
    {
        return xarEvents::getSubjects();
    }

    public static function getSubscribedEvents()
    {
        //return [
        //    'xarEvents.scope.event' => ['onScopeEvent'],
        //];
        if (!empty(static::$subscribedEvents)) {
            return static::$subscribedEvents;
        }
        $eventList = static::getEventList();
        foreach ($eventList as $event => $info) {
            $eventName = static::getEventName($info['scope'], $event);
            static::$subscribedEvents[$eventName] = 'onScopeEvent';
        }
        return static::$subscribedEvents;
    }
}

class HookSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    protected static $eventNamePrefix = 'xarHooks';
    protected static $subscribedEvents = [];
    // could be used to extend with other methods like workflow
    protected static $eventTypeMethods = [
        'scope' => [
            'event' => 'onScopeEvent',
        ],
    ];

    public function notify($type, $subject, $context = null)
    {
        $response = xarHooks::notify($type, $subject, $context);
        return $response;
    }

    public static function getEventList()
    {
        return xarHooks::getSubjects();
    }
}

// @todo unused for now
class EventCallbackSubscriber extends EventSubscriber implements EventSubscriberInterface
{
    protected static $eventNamePrefix = 'xarEvents';
    protected static $subscribedEvents = [];
    // could be used to extend with other methods like workflow
    protected static $eventTypeMethods = [
        'scope' => [
            'event' => 'onScopeEvent',
        ],
    ];
    protected static $callbackFunctions = [];
    public static $moduleName;

    public function callBack($event, string $eventName)
    {
        if (empty(static::$callbackFunctions[$eventName])) {
            return;
        }
        foreach (static::$callbackFunctions[$eventName] as $callbackFunc) {
            try {
                $callbackFunc($event, $eventName);
            } catch (Exception $e) {
                //xarLog::message("Error in callback for $eventName: " . $e->getMessage(), xarLog::LEVEL_INFO);
                echo "Error in callback for $eventName: " . $e->getMessage();
            }
        }
    }

    public function onScopeEvent($event, string $eventName = '')
    {
        //echo static::class . " onScopeEvent got $eventName = " . var_export($event, true) . "\n";
        //$subject = $event->getSubject();
        //[$prefix, $scope, $type] = explode('.', $eventName);
        //$context = $event->getContext();
        $this->callBack($event, $eventName);
    }

    public static function addSubscribedEvent(string $eventScope, string $eventType, string $eventMethod = 'onScopeEvent', ?callable $callbackFunc = null)
    {
        $eventName = static::getEventName($eventScope, $eventType);
        //static::$subscribedEvents[$eventName] = [static::$eventTypeMethods[$eventScope][$eventType]];
        static::$subscribedEvents[$eventName] = $eventMethod;
        if (!empty($callbackFunc)) {
            static::addCallbackFunction($eventName, $callbackFunc);
        }
        return $eventName;
    }

    public static function addCallbackFunction(string $eventName, callable $callbackFunc)
    {
        static::$callbackFunctions[$eventName] ??= [];
        // @checkme call only once per event even if specified several times?
        static::$callbackFunctions[$eventName][] = $callbackFunc;
    }

    public static function getCallbackFunction($class, $method)
    {
        $handler = function ($event, $eventName) use ($class, $method) {
            return call_user_func([$class, $method], $event, $eventName);
        };
        return $handler;
    }
}

// @todo unused for now
class HookCallbackSubscriber extends EventCallbackSubscriber implements EventSubscriberInterface
{
    protected static $eventNamePrefix = 'xarHooks';
    protected static $subscribedEvents = [];
    // could be used to extend with other methods like workflow
    protected static $eventTypeMethods = [
        'scope' => [
            'event' => 'onScopeEvent',
        ],
    ];
    protected static $callbackFunctions = [];
    public static $moduleName;

    public function notify($type, $subject, $context = null)
    {
        xarHooks::notify($type, $subject, $context);
    }

    public static function getEventList()
    {
        return xarHooks::getSubjects();
    }
}
