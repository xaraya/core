<?php
/**
 * Tester for EventObserverBridge and HookObserverBridge
 *
 * By default, any call to xarEvents::notify or xarHooks::notify can trigger an event dispatch as well, so it's up
 * to the event/hook observer bridges and your event subscribers to select which events they want to listen to.
 * An example of a test event subscriber is available here:
 *
 * use Xaraya\Bridge\Events\TestObserverBridgeSubscriber;
 *
 * // have an event subscriber show interest in a few events and/or hooks - see below
 * $subscriber = new TestObserverBridgeSubscriber(['Event'], ['ItemUpdate']);
 * // and add it to the event dispatcher to see something happen
 * $dispatcher->addSubscriber($subscriber);
 *
 *
 * Testers for EventListenerProvider and HookListenerProvider
 *
 * The step from $event + $args to create $subject is simulated in TestEventListeners and TestHookListeners for
 * different potentially interesting subject modules (+ itemtypes), i.e. those which are attached to a listener
 * (hook observer) for that event.
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * use Xaraya\Bridge\Events\TestEventListeners;
 * use Xaraya\Bridge\Events\TestHookListeners;
 *
 * $provider = new TestHookListeners();
 * $provider->dump();
 */

namespace Xaraya\Bridge\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Exception;
use xarMod;
use sys;

sys::import('xaraya.events');
sys::import('xaraya.hooks');
use xarEvents;
use xarHooks;

/**
 * Test the event observer bridges in observers.php by subscribing to a few events and/or hooks here
 */
class TestObserverBridgeSubscriber implements EventSubscriberInterface
{
    public static $subscribedEvents = [];

    public function __construct(array $eventList = ['Event'], array $hookList = ['ItemUpdate'])
    {
        static::addEventList($eventList);
        static::addHookList($hookList);
    }

    public static function onDispatchedEvent($event, string $eventName = '')
    {
        $subject = $event->getSubject();
        echo "Dispatched Event $eventName: " . var_export($subject, true) . "\n";
    }

    public static function onDispatchedHook($event, string $eventName = '')
    {
        $subject = $event->getSubject();
        echo "Dispatched Hook $eventName: " . var_export($subject, true) . "\n";
    }

    public static function addEventList(array $eventList = [])
    {
        $infoList = EventObserverBridge::getEventList();
        foreach ($eventList as $event) {
            if (!array_key_exists($event, $infoList)) {
                throw new Exception("Unknown event '$event'");
            }
            $info = $infoList[$event];
            $eventName = EventObserverBridge::getEventName($info['scope'], $event);
            static::$subscribedEvents[$eventName] = 'onDispatchedEvent';
        }
    }

    public static function addHookList(array $hookList = [])
    {
        $infoList = HookObserverBridge::getEventList();
        foreach ($hookList as $event) {
            if (!array_key_exists($event, $infoList)) {
                throw new Exception("Unknown hook '$event'");
            }
            $info = $infoList[$event];
            $eventName = HookObserverBridge::getEventName($info['scope'], $event);
            static::$subscribedEvents[$eventName] = 'onDispatchedHook';
        }
    }

    public static function getSubscribedEvents()
    {
        return static::$subscribedEvents;
    }
}

/**
 * Test the EventListenerProvider in listeners.php by creating a subject for each event and get all listeners for them
 */
class TestEventListeners extends EventListenerProvider
{
    public function getEventSubjects()
    {
        if (!empty($this->attached)) {
            return $this->attached;
        }
        $attached = [];
        $eventlist = xarEvents::getObserverModules();
        foreach ($eventlist as $modname => $eventinfo) {
            foreach ($eventinfo as $event => $info) {
                $attached[$info['scope']] ??= [];
                $attached[$info['scope']][$event] ??= [];
                $attached[$info['scope']][$event][$modname] = 1;
            }
        }
        $this->attached = $attached;
        return $this->attached;
    }

    /**
     * Fake an event subject relevant to the subject module
     */
    public function createEventSubject($event, $info)
    {
        // @checkme this may not be what the subject is expecting as $args
        $args = $info;
        $subject = $this->getEventSubject($event, $args);
        if (empty($subject)) {
            echo "Subject: $event OOPS\n";
            exit;
        }
        return $subject;
    }

    public function dump()
    {
        echo "Provider: " . get_class($this) . "\n";
        $events = $this->getEventList();
        //echo var_export($events, true);
        $attached = $this->getEventSubjects();
        foreach ($events as $event => $info) {
            echo "Type: $this->type\n";
            echo "Scope: $info[scope]\n";
            echo "Event: $event\n";
            $name = implode('.', [$this->type, $info['scope'], $event]);
            echo "Name: $name\n";
            $info = $this->getEventInfo($event);
            //echo "Info: " . var_export($info, true) . "\n";
            // @checkme this may not be what the subject is expecting as $args
            // fake an event subject relevant to the subject module
            $subject = $this->createEventSubject($event, $info);
            //echo "Subject: " . var_export($subject, true) . "\n";
            echo "Subject: " . get_class($subject) . "\n";
            if (!empty($attached[$info['scope']]) && !empty($attached[$info['scope']][$event])) {
                $subjects = $attached[$info['scope']][$event];
                echo "Attached: " . var_export($subjects, true) . "\n";
            }
            $listeners = $this->getListenersForEvent($subject);
            echo "Listeners: " . var_export($listeners, true) . "\n";
            echo "\n";
        }
    }
}

/**
 * Test the HookListenerProvider in listeners.php by identifying potential subjects for each event and get all listeners for them
 */
class TestHookListeners extends HookListenerProvider
{
    /**
     * For each event find subject modules that will have listeners (hook observers)
     */
    public function getEventSubjects()
    {
        if (!empty($this->attached)) {
            return $this->attached;
        }
        $attached = [];
        // start with the listeners (observer modules) and which events they listen to (hook observers)
        $hooklist = xarHooks::getObserverModules();
        foreach ($hooklist as $modname => $hookinfo) {
            //echo "Hook list: $modname = " . var_export($hookinfo['scopes'], true) . "\n";
            foreach ($hookinfo['scopes'] as $scope => $events) {
                $attached[$scope] ??= [];
                foreach ($events as $event => $more) {
                    $attached[$scope][$event] ??= [];
                }
            }
            // find out which subject modules they're listening for (hooked)
            $subjects = xarHooks::getObserverSubjects($modname);
            foreach ($subjects as $subject => $info) {
                // itemtype 0 will also apply to all other itemtypes
                foreach ($info as $itemtype => $scopes) {
                    foreach ($scopes as $scope => $check) {
                        //echo "Subject: $subject $itemtype $scope $check\n";
                        if (empty($scope)) {
                            foreach ($hookinfo['scopes'] as $scope => $events) {
                                foreach ($events as $event => $more) {
                                    $attached[$scope][$event][$subject] ??= [];
                                    $attached[$scope][$event][$subject][$itemtype] ??= [];
                                    $attached[$scope][$event][$subject][$itemtype][$modname] = $check;
                                }
                            }
                        } elseif (!empty($hookinfo['scopes'][$scope])) {
                            $events = $hookinfo['scopes'][$scope];
                            foreach ($events as $event => $more) {
                                $attached[$scope][$event][$subject] ??= [];
                                $attached[$scope][$event][$subject][$itemtype] ??= [];
                                $attached[$scope][$event][$subject][$itemtype][$modname] = $check;
                            }
                        }
                    }
                }
            }
        }
        $this->attached = $attached;
        return $this->attached;
    }

    /**
     * Fake an event subject relevant to the subject module
     */
    public function createEventSubject($event, $modname, $itemtype, $info)
    {
        $args = [
            'objectid' => $info['itemid'] ?? '',
            'extrainfo' => $info,
        ];
        // preset the objectid and extrainfo to the subject module we're listening for
        if ($info['scope'] == 'module') {
            $args['objectid'] = $modname;
        }
        $args['extrainfo']['module'] = $modname;
        $args['extrainfo']['module_id'] = xarMod::getRegID($modname);
        $args['extrainfo']['itemtype'] = $itemtype;
        // get an event subject relevant to the subject module
        $subject = $this->getEventSubject($event, $args);
        if (empty($subject)) {
            echo "Subject: $event for $modname $itemtype OOPS\n";
            exit;
        }
        return $subject;
    }

    public function dump()
    {
        echo "Provider: " . get_class($this) . "\n";
        $events = $this->getEventList();
        //echo var_export($events, true);
        $attached = $this->getEventSubjects();
        foreach ($events as $event => $info) {
            echo "Type: $this->type\n";
            echo "Scope: $info[scope]\n";
            echo "Event: $event\n";
            $name = implode('.', [$this->type, $info['scope'], $event]);
            echo "Name: $name\n";
            $info = $this->getEventInfo($event);
            //echo "Info: " . var_export($info, true) . "\n";
            // @checkme this may not be what the subject is expecting as $args
            if (empty($attached[$info['scope']]) || empty($attached[$info['scope']][$event])) {
                // fake an event subject relevant to the subject module
                $subject = $this->createEventSubject($event, 'base', 0, $info);
                //echo "Subject: " . var_export($subject, true) . "\n";
                echo "Subject: " . get_class($subject) . "\n";
                $listeners = $this->getListenersForEvent($subject);
                echo "Listeners: " . var_export($listeners, true) . "\n";
                echo "\n";
                continue;
            }
            $subjects = $attached[$info['scope']][$event];
            // @todo itemtype 0 will also apply to all other itemtypes
            foreach ($subjects as $modname => $itemtypes) {
                foreach ($itemtypes as $itemtype => $hooked) {
                    // fake an event subject relevant to the subject module
                    $subject = $this->createEventSubject($event, $modname, $itemtype, $info);
                    //echo "Subject: $modname $itemtype " . var_export($subject, true) . "\n";
                    echo "Subject: $modname $itemtype " . get_class($subject) . "\n";
                    echo "Hooked: " . var_export($hooked, true) . "\n";
                    // itemtype 0 will also apply to all other itemtypes
                    if ($itemtype != 0 && !empty($itemtypes[0])) {
                        echo "Generic: " . var_export($itemtypes[0], true) . "\n";
                    }
                    $listeners = $this->getListenersForEvent($subject);
                    echo "Listeners: " . var_export($listeners, true) . "\n";
                    echo "\n";
                }
            }
        }
    }
}
