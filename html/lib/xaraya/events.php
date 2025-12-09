<?php

/**
 * Note: ALL subjects and their observers must be registered into the EMS
 * The EMS makes no assumptions about a response when an event is notified
 * Event designers should document any requirements
 * Each event is responsible for returning its own response
 * Each event is responsible for handling any responses from its own observers
 * Event designers should document any requirements
 *
 * By default Xaraya expects to use the following standards for events and hooks in classes:
 * // File path = code/modules/{mymodule}/class/{hookobservers}/{itemcreate}.php
 * sys::import("modules.{$module}.class.{$type}.{strtolower($event)}");
 * // Class name = {Mymodule}{ItemCreate}{Observer}
 * $classname = ucfirst($module) . $event . $suffix;
 *
 * The classname may be something completely different if you're using namespaces
 * like "Customer\Modules\MyModule\HookObservers\ItemCreateObserver" and it will be
 * automatically detected during registration, but Xaraya will expect modules to
 * follow the file path structure above to find the right file(s) to load first.
**/
/**
 * @TODO: in order to remain transparent, the system raises few exceptions other then php/BL ones
 * Do we need to evaluate use of log messages to keep track of such occurances ?
 * Do we want to implement a debug function, like the one in blocks, and wrap all
 * potential exception raising calls in try / catch clauses ?
**/

// use ixarMod;
use Xaraya\Context\Context;
use Xaraya\Services\EventsService;
use Xaraya\Services\xar;

/**
 * Exception raised by the events subsystem
 *
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class EventRegistrationException extends RegistrationExceptions
{
    protected $message = 'The event "#(1)" is not properly registered';
}

/**
 * Exception raised by the events subsystem
 *
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class DuplicateEventRegistrationException extends EventRegistrationException
{
    protected $message = 'Unable to register event subject "#(1)", already registered by another module';
}

/**
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
interface ixarEvents
{
    public static function getSubjectType();
    public static function getObserverType();
    public static function getObservers(ixarEventSubject $subject);
    public static function registerSubject($event, $scope, $module, $classnameOrArea, $type, $func);
    public static function register($event, $module, $area, $type, $func, $itemtype, $scope, $classname);
    public static function registerObserver($event, $module, $classnameOrArea, $type, $func);
    public static function unregisterSubject($event, $module);
    public static function unregisterObserver($event, $module);
    public static function notify($event, $args = [], $context = null);
    public static function getSubject($event);
    public static function getSubjects();
    public static function fileLoad($info);
}

/**
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @see xar::events()
**/
class xarEvents extends xarObject implements ixarEvents
{
    // Event system itemtypes
    public const SUBJECT_TYPE       = 1;   // System event subjects, handles OBSERVER_TYPE events
    public const OBSERVER_TYPE      = 2;   // System event observers
    public const SUPPORTED_AREAS    = ['class', 'api', 'gui'];

    // keep track of classname as detected in fileLoad() for register()
    protected static $classnames = [];
    // for non-core modules we're only interested in hookobservers for now - this may extend to eventobservers later...
    protected static $classtypes = ['hookobservers'];
    protected static ?EventsService $service = null;

    protected static function service($xar = null): EventsService
    {
        // Note: this is overridden in xarHooks()
        if (!isset(static::$service)) {
            $xar ??= xar::getServicesClass();
            static::$service = $xar->events();
        }
        return static::$service;
    }

    public static function init(array $args = [], $xar = null)
    {
        // static cache for migration
        static::$service = null;
        return static::service($xar)->init($args);
    }

    public static function getSubjectType()
    {
        return xarEvents::SUBJECT_TYPE;
    }

    public static function getObserverType()
    {
        return xarEvents::OBSERVER_TYPE;
    }

    /**
     * public event notifier function
     *
     * @param string $event name of event subject, required
     * @param mixed $args argument(s) to pass to subject, optional, default empty array
     * @param ?Context<string, mixed> $context
     * @return mixed response from subject notify method
    **/
    public static function notify($event, $args = [], $context = null, $xar = null)
    {
        return static::service($xar)->notify($event, $args, $context);
    }

    /**
     * public event registration functions
     *
    **/
    public static function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify')
    {
        return static::service()->registerSubject($event, $scope, $module, $classnameOrArea, $type);
    }

    public static function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify')
    {
        return static::service()->registerObserver($event, $module, $classnameOrArea, $type);
    }

    /**
     * allow others to define callback functions without registering observers e.g. for event bridge (= not saved in database)
     */
    public static function registerCallback($event, $callback)
    {
        return static::service()->registerCallback($event, $callback);
    }

    /**
     * event registration function
     * used internally by registerSubject and registerObserver methods
     *
     * @access public
     * @param string $event name of the event to observer or listener, required
     * @param mixed $module either string name of module, or int regid of module
     * @param string $area, name of area where file can be found (class|gui|api)
     * @param string $type, type of function (eventobserver|eventsubject for class) (user|admin|etc for ap|gui)
     * @param string $func, name of method for class, or name of function for api|gui
     * @param int $itemtype id of event itemtype (event subject/object, hook subject/object)
     * @param string $scope scope of subject events for selective hooks - also part of event name (event|server|session|module|itemtype|item|user|...)
     * @param string $classname fully qualified class name - when using namespaces or custom instead of default class name
     *
     * @throws BadParameterException, DBException, DuplicateEventException
     * @returns bool, true on success
    **/
    /**
     * area, type and func determine where the eventsystem will look for a subject or observer
     * Subjects must be api functions or class methods
     * Observers can be api or gui functions, or class methods
     * some examples
     * xarEvents::registerSubject('MyEvent', 'base', 'class', 'eventsubject', 'notify');
     * Note: by using defaults for area, type and func as above we could have just written
     * xarEvents::registerSubject('MyEvent', 'base);
     * BaseMyEventObserver::notify() in file /base/class/baseobserver/myevent.php
     * xarEvents::registerSubject('OtherEvent', 'roles', 'api', 'user', 'otherevent');
     * xar::mod()->apiFunc('roles', 'user', 'otherevent');
    **/
    final public static function register($event, $module, $area = 'class', $type = 'eventobservers', $func = 'notify', $itemtype = 0, $scope = '', $classname = '')
    {
        return static::service()->register($event, $module, $area, $type, $func, $itemtype, $scope, $classname);
    }

    public static function fileLoad($info, $xar = null)
    {
        return static::service($xar)->fileLoad($info);
    }


    public static function unregisterSubject($event, $module)
    {
        return static::service()->unregisterSubject($event, $module);
    }

    public static function unregisterObserver($event, $module)
    {
        return static::service()->unregisterObserver($event, $module);
    }

    /**
     * get functions
    **/
    /**
     * Get db info for an event subject
     *
     * @param string $event name of event subject, required
     * @return mixed array of subject info or bool false
     * used internally by the event system, must not be overloaded
    **/
    final public static function getSubject($event, $xar = null)
    {
        return static::service($xar)->getSubject($event);
    }

    /**
     * Load all subjects from db for current subject type
     * We only ever do this once per page request, results are cached
     * @return array<mixed>|void containing subjects, indexed by event name
     * used internally by the event system, must not be overloaded
    **/
    final public static function getSubjects($xar = null)
    {
        return static::service($xar)->getSubjects();
    }

    /**
     * Get all observers of an event subject from db
     *
     * @param ixarEventSubject $subject ixarEventSubject
     * @return array<mixed>|void containing subject observers
    **/
    public static function getObservers(ixarEventSubject $subject)
    {
        $xar = $subject->getServicesClass();
        return static::service($xar)->getObservers($subject);
    }

    public static function getObserverModules($xar = null)
    {
        return static::service($xar)->getObserverModules();
    }

}
