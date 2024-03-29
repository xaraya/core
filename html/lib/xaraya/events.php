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

sys::import("xaraya.structures.events.subject");
sys::import("xaraya.context.context");
use Xaraya\Context\Context;

/**
 * Exception raised by the events subsystem
 *
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
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
 * @version 2.4.0
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
 * @version 2.4.0
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
    public static function registerSubject($event,$scope,$module,$classnameOrArea,$type,$func);
    public static function register($event,$module,$area,$type,$func,$itemtype,$scope,$classname);
    public static function registerObserver($event,$module,$classnameOrArea,$type,$func);
    public static function unregisterSubject($event,$module);
    public static function unregisterObserver($event,$module);
    public static function notify($event, $args = [], $context = null);
    public static function getSubject($event);
    public static function getSubjects();
    public static function fileLoad($info);
}

/**
 * @package core\events
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class xarEvents extends xarObject implements ixarEvents
{
    // Event system itemtypes 
    const SUBJECT_TYPE       = 1;   // System event subjects, handles OBSERVER_TYPE events
    const OBSERVER_TYPE      = 2;   // System event observers
    const SUPPORTED_AREAS    = ['class', 'api', 'gui'];

    // keep track of classname as detected in fileLoad() for register()
    protected static $classnames = [];
    // for non-core modules we're only interested in hookobservers for now - this may extend to eventobservers later...
    protected static $classtypes = ['hookobservers'];
    // allow others to define callback functions without registering observers e.g. for event bridge
    protected static $callbackFunctions = [];

    public static function init(array $args = array())
    {
        // Register tables this subsystem uses
        $tables = array('eventsystem' => xarDB::getPrefix() . '_eventsystem');
        xarDB::importTables($tables);
        return true;
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
    public static function notify($event, $args = [], $context = null)
    {
        $info = array();
        // Attempt to load subject 
        try {
            // get info for specified event
            $info = static::getSubject($event);
            if (empty($info)) return;
            // file load takes care of validation for us 
            if (!self::fileLoad($info)) return; 
            if (!isset($context)) {
                //$context = ContextFactory::fromGlobals(__METHOD__);
                $context = new Context(['source' => __METHOD__]);
            }
            $module = $info['module'];
            switch (strtolower($info['area'])) {
                // support namespaces in modules (and core someday) - we may use $info['classname'] here
                case 'class':
                    // define class (loadFile already checked it exists) 
                    $classname = $info['classname'] ?: ucfirst($module) . $info['event'] . "Subject";
                    // create subject instance, passing $args from caller
                    $subject = new $classname($args);
                    // set context if available in notify call
                    $subject->setContext($context);
                    // get observer info from subject
                    $obsinfo = static::getObservers($subject);
                    if (!empty($obsinfo)) {
                        foreach ($obsinfo as $obs) {
                            // Attempt to load observer
                            try {
                                if (!self::fileLoad($obs)) continue;
                                $obsmod = $obs['module'];
                                $obs['module'] = $obsmod;
                                switch (strtolower($obs['area'])) {
                                    // support namespaces in modules (and core someday) - we may use $obs['classname'] here
                                    case 'class':
                                    default:
                                        // use the defined class for the observer
                                        $obsclass = $obs['classname'] ?: ucfirst($obsmod) . $obs['event'] . "Observer";
                                        // attach observer to subject + pass along $obs to constructor here too
                                        $subject->attach(new $obsclass($obs));
                                    break;
                                    case 'api':
                                        // wrap api function in apiclass observer
                                        sys::import("xaraya.structures.events.apiobserver");
                                        $obsclass = "ApiEventObserver";
                                        $subject->attach(new $obsclass($obs));
                                    break;
                                    case 'gui':
                                        // wrap gui function in guiclass observer
                                        sys::import("xaraya.structures.events.guiobserver");
                                        $obsclass = "GuiEventObserver";
                                        $subject->attach(new $obsclass($obs));
                                    break;                            
                                } 
                            } catch (Exception $e) {
                                // Event system never fails, ever!
                                continue;
                            }
                        }
                    }
                    $method = !empty($info['func']) ? $info['func'] : 'notify';
                    // always notify the subject, even if there are no observers
                    $response = $subject->$method();
                break;
                case 'api':
                    $response = xarMod::apiFunc($module, $info['type'], $info['func'], $args, $context);
                break;
                case 'gui':
                    // not allowed in event subjects
                    default:                
                    $response = false;
                break;          
            }
        } catch (Exception $e) {
            // Events never fail, ever!
            xarLog::message("xarEvents::notify: failed notifying $event subject observers", xarLog::LEVEL_EMERGENCY);
            xarLog::message("xarEvents::notify: Reason: " . $e->getMessage(), xarLog::LEVEL_INFO);
            $response = false;
        }
        
        $info['event'] ??= $event;
        $info['caller'] = static::class;
        $info['args'] = $args;
        // allow others to define callback functions without registering observers e.g. for event bridge (= not saved in database)
        if (!empty(static::$callbackFunctions) && !empty(static::$callbackFunctions[$event])) {
            foreach (static::$callbackFunctions[$event] as $callback) {
                try {
                    call_user_func($callback, $info, $context);
                } catch (Exception $e) {
                    xarLog::message("xarEvents::notify: callback $event error " . $e->getMessage(), xarLog::LEVEL_INFO);
                }
            }
        }
        // allow others to add callback functions by request and pass them via the context e.g. session middleware for reactphp.php
        if (!empty($context) && !empty($context['EventCallback']) && !empty($context['EventCallback'][$event])) {
            $callbackList = $context['EventCallback'][$event];
            foreach ($callbackList as $callback) {
                try {
                    call_user_func($callback, $info, $context);
                } catch (Exception $e) {
                    xarLog::message("xarEvents::notify: callback $event error " . $e->getMessage(), xarLog::LEVEL_INFO);
                }
            }
        }

        // now notify Event subject observers that an event was just raised
        // (these are generic listeners that observe every event raised)
        // We only do this if this isn't the generic Event itself...
        if ($event != 'Event') 
            xarEvents::notify('Event', $info, $context);

        // return the response
        return $response;
        
    }

    /**
     * public event registration functions
     *
    **/
    public static function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify')
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), self::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        $subjecttype = static::getSubjectType();
        $info = self::register($event, $module, $area, $type, $func, $subjecttype, $scope, $classname);
        if (empty($info)) return;
        return $info['id'];
    }

    public static function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify')
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), self::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        $observertype = static::getObserverType();
        // always empty for observers - used for selective hook observers to a particular subject scope (module/itemtype/item/...)
        $scope = '';
        $info = self::register($event, $module, $area, $type, $func, $observertype, $scope, $classname);
        if (empty($info)) return;
        return $info['id'];
    }

    /**
     * allow others to define callback functions without registering observers e.g. for event bridge (= not saved in database)
     */
    public static function registerCallback($event, $callback)
    {
        static::$callbackFunctions[$event] ??= [];
        static::$callbackFunctions[$event][] = $callback;
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
     * xarMod::apiFunc('roles', 'user', 'otherevent');
    **/
    
    final public static function register($event, $module, $area = 'class', $type = 'eventobservers', $func = 'notify', $itemtype = 0, $scope = '', $classname = '')
    {

        $module_id = xarMod::getRegID($module);
        // support namespaces in modules (and core someday) - we may pass along $info['classname'] here too
        $info = array(
            'event'    => $event,
            'module'   => $module,
            'module_id' => $module_id,
            'area'     => $area,
            'type'     => $type,
            'func'     => $func,
            'itemtype' => $itemtype,
            'classname' => $classname,
            'scope'    => $scope,
        );

        // file load takes care of validation, any invalid input throws an exception 
        if (!self::fileLoad($info)) return;

        // keep track of classname as detected in fileLoad() for register()
        if ($area == 'class' && empty($info['classname']) && in_array($info['type'], static::$classtypes)) {
            $classkey = implode(':', [$info['event'], $info['module'], $info['type']]);
            if (!empty(static::$classnames[$classkey])) {
                $info['classname'] = static::$classnames[$classkey];
            }
        }

        if ($itemtype == static::getSubjectType()) {
            // see if subject is already registered            
            $subject = static::getSubject($event);
            // event subjects must be unique! (event, module, itemtype)
            if (!empty($subject)) {
                if ($subject['module'] == $module) {
                    // same module, registering same event subject
                    // unregister the event so it can be re-registered ( = updated :) )
                    if (!static::unregisterSubject($event, $module)) return;
                } else {
                    // CHECKME: doesn't unique mean we can have the same subject for different modules?
                    // oops, that event is already registered by another module, pick a different one!
                    // throw new DuplicateEventRegistrationException($event);
                }
            }
        } elseif ($itemtype == static::getObserverType()) {
             // event observers don't need to be unique, but each module can 
             // only register one observer per event subject
             // unregister the event so it can be re-registered ( = updated :) )
             if (!static::unregisterObserver($event, $module)) return;
        }
        
         // create entry in db
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $emstable = $tables['eventsystem'];
        // support namespaces in modules (and core someday) - we may save $info['classname'] here
        $query = "INSERT INTO $emstable 
                  (
                  event,
                  module_id,
                  area,
                  type,
                  func,
                  itemtype,
                  class,
                  scope
                  )
                  VALUES (?,?,?,?,?,?,?,?)";

        $bindvars = array();
        $bindvars[] = $event;
        $bindvars[] = $module_id;
        $bindvars[] = $area;
        $bindvars[] = $type;
        $bindvars[] = $func;
        $bindvars[] = $itemtype;
        // support namespaces in modules (and core someday) - we may save $info['classname'] here
        $bindvars[] = $info['classname'];
        $bindvars[] = $scope;

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        
        $id = $dbconn->getLastId($emstable);
        if (empty($id)) return;
        $info['id'] = $id;
        $info['module_id'] = $module_id;
        
        return $info;
    }
    
    public static function fileLoad($info)
    {
        extract($info);
        
        // validate input, some methods (register/notify) use this to validate input     
        $invalid = array();
        // Check we have a valid event
        /** @var string $event */
        if (empty($event) || !is_string($event) || strlen($event) > 255)
            $invalid[] = 'event';
                    
        // Check we have a valid module
        /** @var string $module */
        if (empty($module) || is_numeric($module) || empty($module_id) || !is_numeric($module_id)) {
            if (!empty($module)) {
                $module_id = is_numeric($module) ? $module : xarMod::getRegID($module);
            }
            /** @var int $module_id */
            if (!empty($module_id))
                $modinfo = xarMod::getInfo($module_id);
            // can't check mod available here, since it may not be if the module is init'ing
            /** @var array<mixed> $modinfo */
            //if (empty($modinfo) || !xarMod::isAvailable($modinfo['name']))
            if (!empty($modinfo)) {
                $module = $modinfo['name'];
            } else {
                $invalid[] = 'module';
            }
        }

        // Check we have a valid area (class, api, gui)
        /** @var string $area */
        if (empty($area) || !is_string($area) || strlen($area) > 64)
            $invalid[] = 'area';

        // Check we have a valid type (eventobserver, eventsubject, admin, user, event, etc)
        /** @var string $type */
        if (empty($type) || !is_string($type) || strlen($type) > 64)
            $invalid[] = 'type';        

        // Check we have a valid func
        /** @var string $func */
        if (empty($func) || !is_string($func) || strlen($func) > 64)
            $invalid[] = 'func';
        
        /** @var int $itemtype */
        if (empty($itemtype) || !is_numeric($itemtype)) {
            // not a valid subject or observer itemtype 
            $invalid[] = 'itemtype';
        }                        
        
        if (!empty($invalid)) {
            $vars = array(join(', ', $invalid), 'register', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }
        
        $area = strtolower($area);
        static $_files = array();
        if (isset($_files[$itemtype][$event][$module]))
            return $_files[$itemtype][$event][$module];
        $loaded = false;
        
        $suffix = '';
        switch ($area) {
            case 'class':
            default:
                if ($itemtype == static::getSubjectType()) {
                    $suffix = 'Subject';
                } elseif ($itemtype == static::getObserverType()) {
                    $suffix = 'Observer';
                }
                if (empty($func)) $func = 'notify';
                $filename = strtolower($event);
                // support namespaces in modules (and core someday) - we may detect or use $info['classname'] here
                if (empty($info['classname'])) {
                    // for non-core modules we're only interested in hookobservers for now - this may extend to eventobservers later...
                    if (in_array($info['type'], static::$classtypes)) {
                        // we try to get the actual $classname here first
                        $oldclasses = get_declared_classes();
                        // import the file (raises exception if file not found)
                        sys::import("modules.{$module}.class.{$type}.{$filename}");
                        $newclasses = get_declared_classes();
                        // assuming new classes in namespaces only have 1 class definition per file as they should...
                        $diffclasses = array_values(array_diff($newclasses, $oldclasses, ['HookObserver', 'EventObserver', 'HookSubject', 'EventSubject']));
                        xarLog::message("xarEvents::fileLoad: found classes " . implode(', ', $diffclasses), xarLog::LEVEL_INFO);
                        if (count($diffclasses) > 0) {
                            $classname = $diffclasses[0];
                        } else {
                            $classname = ucfirst($module) . $event . $suffix;
                        }
                        // keep track of classname as detected in fileLoad() for register()
                        $classkey = implode(':', [$info['event'], $info['module'], $info['type']]);
                        static::$classnames[$classkey] = $classname;
                    } else {
                        // import the file (raises exception if file not found)
                        sys::import("modules.{$module}.class.{$type}.{$filename}");
                        $classname = ucfirst($module) . $event . $suffix;
                    }
                } else {
                    // import the file (raises exception if file not found)
                    sys::import("modules.{$module}.class.{$type}.{$filename}");
                    $classname = $info['classname'];
                }
                if (!class_exists($classname))
                    throw new ClassNotFoundException($classname);
                if (!method_exists($classname, $func))
                    throw new FunctionNotFoundException($func);
                // one class file loaded :)
                $loaded = true;
            break;
            
            case 'gui':
            case 'api':
                // use name of event as function name if none specified
                if (empty($func)) $func = strtolower($event);
                // use name of func as filename
                $filename = strtolower($func);
                // determine the type folder to look in (xartype|xartypeapi) 
                $type = $area == 'gui' ? $type : $type . $area;
                // define the function name (module_xartype(api)_func);
                $func = $module .'_' . $type . '_' . $filename;
                // @checkme by importing the function directly here, we never call xarMod::apiLoad($module, $type)
                // or xarMod::load($module, $type) in xarMod::callFunc() later when calling the function in observer
                // import the file (raises exception if file not found) 
                try {
                    // try for specific file in type folder (eg /module/xaruserapi/eventfunc.php)
                    // we want to catch any exception here so we can fall back
                    sys::import("modules.{$module}.xar{$type}.{$filename}");
                } catch (Exception $e) {
                    // fall back to generic type file (eg /module/xaruserapi.php)
                    // we don't catch any exception here 
                    sys::import("modules.{$module}.xar{$type}");
                }
                // check function exists
                if (!function_exists($func))
                    throw new FunctionNotFoundException($func);
                // one function file loaded :) 
                $loaded = true;
            break;
        } 
        return $_files[$itemtype][$event][$module] = $loaded;
    }
        

    public static function unregisterSubject($event, $module)
    {
        $subjecttype = static::getSubjectType();
        if (!self::unregister($event, $module, $subjecttype)) return;
        return true;
    }
    
    public static function unregisterObserver($event, $module)
    {
        $observertype = static::getObserverType();
        if (!self::unregister($event, $module, $observertype)) return;
        return true;
    }

    
    private static function unregister($event, $module, $itemtype)
    {
        // Validate the input        
        $invalid = array();
        if (empty($event) || !is_string($event) || strlen($event) > 255)
            $invalid[] = 'event';
        if (empty($module) || (!is_string($module) && !is_numeric($module)) ) {
            $invalid[] = 'module';
        }
        if (empty($itemtype) || !is_numeric($itemtype)) {
            $invalid[] = 'itemtype';
        }
        
        // Assemble the query
        sys::import('xaraya.structures.query');
        $tables = xarDB::getTables();
        $q = new Query('DELETE', $tables['eventsystem']);
        $q->eq('itemtype', $itemtype);
        // @deprecated 2.4.1 this hasn't been around in a long while
        if (strtoupper($event) != 'ALL') {
            $q->eq('event', $event);
        }
        
        // @deprecated 2.4.1 this hasn't been around in a long while
        if (strtoupper($module) != 'ALL') {
            if (is_numeric($module)) {
                $module_id = $module;
            } else {
                $module_id = xarMod::getRegID($module);
            }
            if (!empty($module_id))
                $modinfo = xarMod::getInfo($module_id);
            if (empty($modinfo))
                $invalid[] = 'module';
            $q->eq('module_id', $module_id);
        }
        if (!empty($invalid)) {
            $vars = array(join(', ', $invalid), 'register', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }
                
        // Remove the event item
        $result = $q->run();
        if (!$result) return;

        return true;
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
    final public static function getSubject($event)
    {
        // init the cache, if it isn't already init'ed
        $subjects = static::getSubjects();
        if (!isset($subjects[$event]))
            $subjects[$event] = array();
        return $subjects[$event];
    }

    /**
     * Load all subjects from db for current subject type
     * We only ever do this once per page request, results are cached
     * @return array<mixed>|void containing subjects, indexed by event name
     * used internally by the event system, must not be overloaded 
    **/
    final public static function getSubjects()
    {
        $subjecttype = static::getSubjectType();
        // Cached event subjects and observers
        $cacheScope = 'Events.Subjects';
        $cacheName = $subjecttype;
        if (xarCoreCache::isCached($cacheScope, $cacheName)) {
            $subjects = xarCoreCache::getCached($cacheScope, $cacheName);
            return $subjects;
        }
        
        // initialize the cache (we only ever run this query once per subject type)
        $subjects = array();

        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        // support namespaces in modules (and core someday) - we may get back $classname here
        $query = "SELECT es.id, es.event, es.module_id, es.area, es.type, es.func, es.itemtype, es.class, es.scope,
                         ms.name
                  FROM $etable es, $mtable ms";
        // get subjects for valid, active modules only 
        $where[] = "es.module_id = ms.regid";
        $where[] = "ms.state = ?";
        $bindvars[] = xarMod::STATE_ACTIVE;
        // get subjects for current subjecttype
        $where[] = "es.itemtype = ?";
        $bindvars[] = $subjecttype;        
        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event (doesn't really matter for subjects)
        $query .= " ORDER BY ms.name ASC, es.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        while($result->next()) {
            list($id, $event, $module_id, $area, $type, $func, $itemtype, $classname, $scope, $module) = $result->fields;
            // cache results            
            $subjects[$event] = array(
                'id' => $id,
                'event' => $event,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
                'classname' => $classname,
                'scope' => $scope,
            );
        };
        $result->close();
        // return cached results
        xarCoreCache::setCached($cacheScope, $cacheName, $subjects);
        return $subjects;
    }

    /**
     * Get all observers of an event subject from db
     *
     * @param ixarEventSubject $subject ixarEventSubject
     * @return array<mixed>|void containing subject observers
    **/
    public static function getObservers(ixarEventSubject $subject)
    {
        $event = $subject->getSubject();
        $info = static::getSubject($event);
        $subjecttype = static::getSubjectType();
        if (empty($info) || $info['itemtype'] != $subjecttype) 
            return array();
        $observertype = static::getObserverType();
        // Cached event subjects and observers
        $cacheScope = 'Events.Observers';
        $cacheName = $observertype;
        $observers = array();
        if (xarCoreCache::isCached($cacheScope, $cacheName)) {
            $observers = xarCoreCache::getCached($cacheScope, $cacheName);
            if (isset($observers[$event])) {
                return $observers[$event];
            }
        }
        
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        //$htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        // support namespaces in modules (and core someday) - we may get back $classname here
        // get all registered observers to registered subjects
        $query = "SELECT o.id, o.event, o.module_id, mo.name, o.area, o.type, o.func, o.itemtype, o.class
                  FROM $etable o, $etable s, $mtable mo, $mtable ms";
        
        // make sure we only get observers to registered subjects :)  
        $where[] =  "o.event = s.event";
        
        // only get subjects belonging to a registered module
        $where[] = "ms.regid = s.module_id";
         // only get subjects of active modules
        $where[] = "ms.state = ?";
        $bindvars[] = xarMod::STATE_ACTIVE;
        // only get subjects for the current subject itemtype
        $where[] =  "s.itemtype = ?";
        $bindvars[] = $subjecttype;
        // only get observers belonging to a registered module
        $where[] = "mo.regid = o.module_id";
        // only get observers of active modules
        $where[] = "mo.state = ?";
        $bindvars[] = xarMod::STATE_ACTIVE;
        // only get observers for the current observer itemtype
        $where[] = "o.itemtype = ?";
        $bindvars[] = $observertype;
        // only observers of this event subject - we take all events at once now
        //$where[] = "s.event = ?";
        //$bindvars[] = $event;

        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        $query .= " ORDER BY mo.name ASC, o.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        while($result->next()) {
            list($id, $evt, $module_id, $module, $area, $type, $func, $itemtype, $classname) = $result->fields;
            $observers[$evt] ??= array();
            $observers[$evt][$module] = array(
                'id' => $id,
                'event' => $evt,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
                'classname' => $classname,
            );
        };
        if (!isset($observers[$event]))
            $observers[$event] = array();

        xarCoreCache::setCached($cacheScope, $cacheName, $observers);
        return $observers[$event];
    }

    public static function getObserverModules()
    {
        $observertype = static::getObserverType();
        static $_modules;
        if (isset($_modules[$observertype])) {
            return $_modules[$observertype];
        }
        $_modules[$observertype] = array();
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        //$htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        // support namespaces in modules (and core someday) - we may get back $classname here
        $query = "SELECT eo.id, eo.event, eo.module_id, eo.area, eo.type, eo.func, eo.itemtype, eo.class,
                         mo.name,
                         es.scope
                  FROM $etable eo, $etable es, $mtable mo, $mtable ms";
        // get only observers with a corresponding subject registered
        $where[] = "eo.event = es.event";
        // make sure they belong to a valid module
        $where[] = "eo.module_id = mo.regid";
        // make sure they belong to an active module
        $where[] = "mo.state = ?";
        $bindvars[] = xarMod::STATE_ACTIVE;
        $where[] = "ms.state = ?";
        $bindvars[] = xarMod::STATE_ACTIVE;
        // only observers of current observer itemtype
        $where[] = "eo.itemtype = ?";
        $bindvars[] = $observertype;
        // only subjects of current subject itemtype
        $where[] = "es.itemtype = ?";
        $bindvars[] = static::getSubjectType();        

        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        $query .= " ORDER BY mo.name ASC, eo.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        while($result->next()) {
            list($id, $evt, $module_id, $area, $type, $func, $itemtype, $classname, $modname, $scope) = $result->fields;
            if (!isset($_modules[$observertype][$modname]))
                $_modules[$observertype][$modname] = array();
            $_modules[$observertype][$modname][$evt] = array(
                'id' => $id,
                'event' => $evt,
                'module_id' => $module_id,
                'module' => $modname,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
                'classname' => $classname,
                'scope' => $scope,
            );
        } 
        return $_modules[$observertype];
    }
     
}
