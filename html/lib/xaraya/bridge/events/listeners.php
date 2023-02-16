<?php
/**
 * PSR-14 Event Listener Providers for ixarEventSubject and ixarHookSubject events (work in progress)
 *
 * Not really useful here, but based on what happens in xarEvents::notify() once the $subject is created.
 * Event dispatchers and/or event subscribers (PSR-14 or otherwise) could potentially use these to:
 *   1. create $subject from incoming $event + $args, and
 *   2. get a list of listeners to call for that $subject, or
 *   3. subscribe all listeners for a hook module (event subscriber)
 * to dispatch events without ever calling xarEvents::notify() itself. For a more practical way to bridge events to
 * and from the Xaraya eventsystem, see event subscribers (App -> Xaraya) and event observer bridges (Xaraya -> App).
 *
 * The step from $event + $args to create $subject is simulated in TestEventListeners and TestHookListeners for
 * different potentially interesting subject modules (+ itemtypes), i.e. those which are attached to a listener
 * (hook observer) for that event - see lib/xaraya/bridge/events/testers.php
 *
 * require dirname(__DIR__).'/vendor/autoload.php';
 * sys::init();
 * xarCache::init();
 * xarCore::xarInit(xarCore::SYSTEM_USER);
 *
 * use Symfony\Component\EventDispatcher\EventDispatcher;
 * use Xaraya\Bridge\Events\EventListenerProvider;
 * use Xaraya\Bridge\Events\HookListenerProvider;
 *
 * $dispatcher = new EventDispatcher();
 *
 * $provider = new HookListenerProvider();
 *
 * $itemid = spl_object_id($provider);
 * $args = ['module' => 'dynamicdata', 'itemtype' => 3, 'itemid' => $itemid];
 * $subject = $provider->getEventSubject('ItemCreate', $args);
 *
 * $listeners = $provider->getListenersForEvent($subject);
 * foreach ($listeners as $listener) {
 *     $wrapper = $provider->wrapListener($listener, $provider->responses);
 *     $dispatcher->addListener('ItemCreate', $wrapper);
 * }
 *
 * $event = $dispatcher->dispatch($subject, 'ItemCreate');
 * echo "Responses: " . var_export($provider->responses, true) . "\n";
 *
 */

namespace Xaraya\Bridge\Events;

use Psr\EventDispatcher\ListenerProviderInterface;
use Exception;
use xarMod;
use sys;

sys::import('xaraya.events');
sys::import('xaraya.hooks');
sys::import('xaraya.structures.events.apiobserver');
sys::import('xaraya.structures.events.guiobserver');
use xarEvents;
use xarHooks;
use ixarEventSubject;
use ixarHookSubject;

/**
 * Listeners (observers) in Xaraya depend on $subject, created from $event and $args - especially for hooks
 */
class EventListenerProvider implements ListenerProviderInterface
{
    public $type = 'xarEvents';
    public $attached = [];
    public $responses = [];

    public function getListenersForEvent(object $subject): iterable
    {
        if (!($this->checkSubject($subject))) {
            return [];
        }
        //$info = xarEvents::getSubject($event);
        //$subject = new $classname($args);
        //$event = $subject->getSubject();
        $obsinfo = $this->getObservers($subject);
        return $this->getObserverCallables($obsinfo);
    }

    /**
     * Wrap the listener to keep track of the $responses - $eventName is unused by event/hook observers here
     */
    public function wrapListener($callable, &$responses)
    {
        if (is_array($callable)) {
            if (is_string($callable[0])) {
                $key = $callable[0];
            } elseif (property_exists($callable[0], 'module')) {
                $key = ($callable[0])->module;
            } else {
                $key = get_class($callable[0]);
            }
        } elseif (is_string($callable)) {
            $key = $callable;
        } else {
            $key = spl_object_hash($callable);
        }
        $wrapper = function ($event, string $eventName = '') use ($callable, $key, &$responses) {
            //echo "Event $eventName\n";
            // @checkme apisubject updates the extrainfo in subject each time
            $responses[$key] = call_user_func($callable, $event, $eventName);
        };
        return $wrapper;
    }

    public function checkSubject($subject)
    {
        return $subject instanceof ixarEventSubject;
    }

    public function getObservers($subject)
    {
        $obsinfo = xarEvents::getObservers($subject);
        return $obsinfo;
    }

    public function getEventList()
    {
        return xarEvents::getSubjects();
    }

    public function getEventInfo($event)
    {
        // get info for specified event
        $info = xarEvents::getSubject($event);
        if (empty($info)) {
            return;
        }
        // file load takes care of validation for us
        if (!xarEvents::fileLoad($info)) {
            return;
        }
        return $info;
    }

    /**
     * See xarEvents::notify() on how $subject is created from $event and $args
     */
    public function getEventSubject($event, $args=[])
    {
        // get info for specified event
        $info = $this->getEventInfo($event);
        $module = xarMod::getName($info['module_id']);
        switch (strtolower($info['area'])) {
            // support namespaces in modules (and core someday) - we may use $info['classname'] here
            case 'class':
                // define class (loadFile already checked it exists)
                $classname = $info['classname'] ?: ucfirst($module) . $info['event'] . "Subject";
                // @checkme make sure we refer to global namespace here
                $classname = "\\" . $classname;
                // create subject instance, passing $args from caller
                try {
                    $subject = new $classname($args);
                } catch (Exception $e) {
                    echo "Unable to create subject for $classname with " . var_export($args, true) . "\n";
                    echo "Exception: " . $e->getMessage();
                    return;
                }
                // get observer info from subject
                //$obsinfo = xarEvents::getObservers($subject);
                // ...
                //$method = !empty($info['func']) ? $info['func'] : 'notify';
                // always notify the subject, even if there are no observers
                //$response = $subject->$method();
                return $subject;

            case 'api':
                //$response = xarMod::apiFunc($module, $info['type'], $info['func'], $args);
                break;

            case 'gui':
                // not allowed in event subjects

            default:
                //$response = false;
                break;
        }
    }

    /**
     * See xarEvents::notify() on how $observers are created from $obsinfo = $this->getObservers($subject)
     */
    public function getObserverCallables($obsinfo)
    {
        $callables = [];
        foreach ($obsinfo as $obs) {
            if (!xarEvents::fileLoad($obs)) {
                continue;
            }
            $obsmod = xarMod::getName($obs['module_id']);
            $obs['module'] = $obsmod;
            switch (strtolower($obs['area'])) {
                // support namespaces in modules (and core someday) - we may use $obs['classname'] here
                case 'class':
                default:
                    // use the defined class for the observer
                    $obsclass = $obs['classname'] ?: ucfirst($obsmod) . $obs['event'] . "Observer";
                    break;
                case 'api':
                    // wrap api function in apiclass observer
                    $obsclass = "ApiEventObserver";
                    break;
                case 'gui':
                    // wrap gui function in guiclass observer
                    $obsclass = "GuiEventObserver";
                    break;
            }
            // @checkme make sure we refer to global namespace here
            $obsclass = "\\" . $obsclass;
            // attach observer to subject + pass along $obs to constructor here too
            //$subject->attach(new $obsclass($obs));
            try {
                $observer = new $obsclass($obs);
            } catch (Exception $e) {
                echo "Unable to create observer for $obsclass with " . var_export($obs, true) . "\n";
                echo "Exception: " . $e->getMessage();
                break;
            }
            // @checkme Symfony EventDispatcher calls $listener($event, $eventName, $this);
            $callables[] = [$observer, 'notify'];
        }
        // $subject->notify() will call all attached observers with $obs->notify($subject);
        return $callables;
    }
}

class HookListenerProvider extends EventListenerProvider implements ListenerProviderInterface
{
    public $type = 'xarHooks';
    public $attached = [];
    public $responses = [];

    public function getListenersForEvent(object $subject): iterable
    {
        if (!($this->checkSubject($subject))) {
            return [];
        }
        //$info = xarHooks::getSubject($event);
        //$subject = new $classname($args);
        //$event = $subject->getSubject();
        $obsinfo = $this->getObservers($subject);
        // @checkme or use getSubjectObservers() instead?
        return $this->getObserverCallables($obsinfo);
    }

    public function checkSubject($subject)
    {
        return $subject instanceof ixarHookSubject;
    }

    public function getObservers($subject)
    {
        $obsinfo = xarHooks::getObservers($subject);
        return $obsinfo;
    }

    public function getEventList()
    {
        return xarHooks::getSubjects();
    }

    public function getEventInfo($event)
    {
        // get info for specified event
        $info = xarHooks::getSubject($event);
        if (empty($info)) {
            return;
        }
        // file load takes care of validation for us
        if (!xarHooks::fileLoad($info)) {
            return;
        }
        return $info;
    }
}
