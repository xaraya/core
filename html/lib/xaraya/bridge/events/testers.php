<?php

/**
 * Tester for EventObserverBridge and HookObserverBridge
 *
 * By default, any call to xar::events()->notify or xar::hooked()->notify can trigger an event dispatch as well, so it's up
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
 * require_once dirname(__DIR__).'/vendor/autoload.php';
 * use Xaraya\Services\xar;
 * sys::init();
 * xar::load(xarCore::SYSTEM_USER);
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
use Xaraya\Services\WithServicesTrait;

/**
 * Test the event observer bridges in observers.php by subscribing to a few events and/or hooks here
 */
class TestObserverBridgeSubscriber implements EventSubscriberInterface
{
    use WithServicesTrait;

    public static $subscribedEvents = [];

    public function __construct(array $eventList = ['Event'], array $hookList = ['ItemUpdate'], $xar = null)
    {
        $this->setServicesClass($xar);
        $this->addEventList($eventList);
        $this->addHookList($hookList);
    }

    public function onDispatchedEvent($event, string $eventName = '')
    {
        $subject = $event->getSubject();
        echo "Dispatched Event $eventName: " . var_export($subject, true) . "\n";
        $context = $event->getContext();
        echo "Dispatched Context: " . var_export($context, true) . "\n";
    }

    public function onDispatchedHook($event, string $eventName = '')
    {
        $subject = $event->getSubject();
        echo "Dispatched Hook $eventName: " . var_export($subject, true) . "\n";
        $context = $event->getContext();
        echo "Dispatched Context: " . var_export($context, true) . "\n";
    }

    public function addEventList(array $eventList = [])
    {
        $xar = $this->getServicesClass();
        //$infoList = EventObserverBridge::getEventList();
        $infoList = $xar->events()->getSubjects();
        foreach ($eventList as $event) {
            if (!array_key_exists($event, $infoList)) {
                throw new Exception("Unknown event '$event'");
            }
            $info = $infoList[$event];
            //xarEvents.scope.event
            $eventName = EventObserverBridge::getEventName($info['scope'], $event);
            static::$subscribedEvents[$eventName] = 'onDispatchedEvent';
        }
    }

    public function addHookList(array $hookList = [])
    {
        $xar = $this->getServicesClass();
        //$infoList = HookObserverBridge::getEventList();
        $infoList = $xar->hooked()->getSubjects();
        foreach ($hookList as $event) {
            if (!array_key_exists($event, $infoList)) {
                throw new Exception("Unknown hook '$event'");
            }
            $info = $infoList[$event];
            //xarHooks.scope.event
            $eventName = HookObserverBridge::getEventName($info['scope'], $event);
            static::$subscribedEvents[$eventName] = 'onDispatchedHook';
        }
    }

    public static function getSubscribedEvents(): array
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
        $xar = $this->getServicesClass();
        $eventlist = $xar->events()->getObserverModules();
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
            \xarCore::exit();
        }
        return $subject;
    }

    public function dump()
    {
        echo "Provider: " . static::class . "\n";
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
            echo "Subject: " . $subject::class . "\n";
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
        $xar = $this->getServicesClass();
        // start with the listeners (observer modules) and which events they listen to (hook observers)
        $hooklist = $xar->hooked()->getObserverModules();
        foreach ($hooklist as $modname => $hookinfo) {
            //echo "Hook list: $modname = " . var_export($hookinfo['scopes'], true) . "\n";
            foreach ($hookinfo['scopes'] as $scope => $events) {
                $attached[$scope] ??= [];
                foreach ($events as $event => $more) {
                    $attached[$scope][$event] ??= [];
                }
            }
            // find out which subject modules they're listening for (hooked)
            $subjects = $xar->hooked()->getObserverSubjects($modname);
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
        $xar = $this->getServicesClass();
        $args['extrainfo']['module'] = $modname;
        $args['extrainfo']['module_id'] = $xar->mod()->getRegID($modname);
        $args['extrainfo']['itemtype'] = $itemtype;
        // get an event subject relevant to the subject module
        $subject = $this->getEventSubject($event, $args);
        if (empty($subject)) {
            echo "Subject: $event for $modname $itemtype OOPS\n";
            \xarCore::exit();
            return;
        }
        return $subject;
    }

    public function dump()
    {
        echo "Provider: " . static::class . "\n";
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
                echo "Subject: " . $subject::class . "\n";
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
                    echo "Subject: $modname $itemtype " . $subject::class . "\n";
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
