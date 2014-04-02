<?php
/**
 * @package core
 * @subpackage events
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
/**
 * Note: ALL subjects and their observers must be registered into the EMS
 * The EMS makes no assumptions about a response when an event is notified
 * Event designers should document any requirements
 * Each event is responsible for returning its own response
 * Each event is responsible for handling any responses from its own observers
 * Event designers should document any requirements
**/
/**
 * @TODO: in order to remain transparent, the system raises few exceptions other then php/BL ones
 * Do we need to evaluate use of log messages to keep track of such occurances ?
 * Do we want to implement a debug function, like the one in blocks, and wrap all
 * potential exception raising calls in try / catch clauses ?
**/

/**
 * Exceptions raised by this subsystem
 *
 */
class EventRegistrationException extends RegistrationExceptions
{
    protected $message = 'The event "#(1)" is not properly registered';
}
class DuplicateEventRegistrationException extends EventRegistrationException
{
    protected $message = 'Unable to register event subject "#(1)", already registered by another module';
}

class xarEvents extends Object implements ixarEvents
{
    // Event system itemtypes 
    const SUBJECT_TYPE       = 1;   // System event subjects, handles OBSERVER_TYPE events
    const OBSERVER_TYPE      = 2;   // System event observers
    
    // Cached event subjects and observers
    // @TODO: evaluate caching
    protected static $subjects;
    protected static $observers;

    public static function init(&$args)
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
     * @param string event name of event subject, required
     * @param mixed $args argument(s) to pass to subject, optional, default empty array
     * @return mixed response from subject notify method
    **/
    public static function notify($event, $args=array())
    {

        // Attempt to load subject 
        try {
            // get info for specified event
            $info = static::getSubject($event);
            if (empty($info)) return;
            // file load takes care of validation for us 
            if (!self::fileLoad($info)) return; 
            $module = xarMod::getName($info['module_id']);
            switch (strtolower($info['area'])) {
                case 'class':
                    // define class (loadFile already checked it exists) 
                    $classname = ucfirst($module) . $info['event'] . "Subject";
                    // create subject instance, passing $args from caller
                    $subject = new $classname($args);
                    // get observer info from subject
                    $obsinfo = static::getObservers($subject);
                    if (!empty($obsinfo)) {
                        foreach ($obsinfo as $obs) {
                            // Attempt to load observer
                            try {
                                if (!self::fileLoad($obs)) continue;
                                $obsmod = xarMod::getName($obs['module_id']);
                                $obs['module'] = $obsmod;
                                switch (strtolower($obs['area'])) {
                                    case 'class':
                                    default:
                                        // use the defined class for the observer
                                        $obsclass = ucfirst($obsmod) . $obs['event'] . "Observer";
                                        // attach observer to subject                
                                        $subject->attach(new $obsclass());
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
                    $response = xarMod::apiFunc($module, $info['type'], $info['func'], $args);
                break;
                case 'gui':
                    // not allowed in event subjects
                    default:                
                    $response = false;
                break;          
            }
        } catch (Exception $e) {
            // Events never fail, ever!
            xarLog::message("xarEvents::notify: failed notifying $event subject observers");
            $response = false;
        }
        
        // now notify Event subject observers that an event was just raised
        // (these are generic listeners that observe every event raised)
        // We only do this if this isn't the generic Event itself...
        if ($event != 'Event') 
            xarEvents::notify('Event', $info);

        // return the response
        return $response;
        
    }

    /**
     * public event registration functions
     *
    **/    
    public static function registerSubject($event,$scope,$module,$area='class',$type='eventsubjects',$func='notify')
    {
        $subjecttype = static::getSubjectType();
        $info = self::register($event, $module, $area, $type, $func, $subjecttype, $scope);
        if (empty($info)) return;
        self::$subjects[$subjecttype][$event] = $info;
        return $info['id'];
    }    
    
    public static function registerObserver($event,$module,$area='class',$type='eventobservers',$func='notify')
    {     
        $observertype = static::getObserverType();  
        $info = self::register($event, $module, $area, $type, $func, $observertype);
        if (empty($info)) return;
        self::$observers[$observertype][$event][$module] = $info;
        return $info['id'];
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
     * @param int $itemtype id of event itemtype
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
    
    final public static function register($event,$module,$area='class',$type='eventobservers',$func='notify', $itemtype, $scope="") 
    {

        $info = array(
            'event' => $event,
            'module' => $module,
            'area' => $area,
            'type' => $type,
            'func' => $func,
            'itemtype' => $itemtype,
            'scope' => $scope,
        );        
                      
        // file load takes care of validation, any invalid input throws an exception 
        if (!self::fileLoad($info)) return;

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
        
        $module_id = xarMod::getRegID($module);        
        
         // create entry in db
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $emstable = $tables['eventsystem'];
        $nextId = $dbconn->GenId($emstable);
        $query = "INSERT INTO $emstable 
                  (
                  id,
                  event,
                  module_id,
                  area,
                  type,
                  func,
                  itemtype,
                  scope
                  )
                  VALUES (?,?,?,?,?,?,?,?)";

        $bindvars = array();
        $bindvars[] = $nextId;
        $bindvars[] = $event;
        $bindvars[] = $module_id;
        $bindvars[] = $area;
        $bindvars[] = $type;
        $bindvars[] = $func;
        $bindvars[] = $itemtype;
        $bindvars[] = $scope;

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        
        $id = $dbconn->PO_Insert_ID($emstable, 'id');
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
        if (empty($event) || !is_string($event) || strlen($event) > 255)
            $invalid[] = 'event';
                    
        // Check we have a valid module
        if (!empty($module)) {
            $module_id = is_numeric($module) ? $module : xarMod::getRegID($module);
        }                    
        if (!empty($module_id))
            $modinfo = xarMod::getInfo($module_id);
        // can't check mod available here, since it may not be if the module is init'ing
        //if (empty($modinfo) || !xarMod::isAvailable($modinfo['name']))
        if (empty($modinfo)) 
            $invalid[] = 'module';       

        // Check we have a valid area (class, api, gui)
        if (empty($area) || !is_string($area) || strlen($area) > 64)
            $invalid[] = 'area';

        // Check we have a valid type (eventobserver, eventsubject, admin, user, event, etc)
        if (empty($type) || !is_string($type) || strlen($type) > 64)
            $invalid[] = 'type';        

        // Check we have a valid func
        if (empty($func) || !is_string($func) || strlen($func) > 64)
            $invalid[] = 'func';
        
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
        $module = $modinfo['name'];
        static $_files = array();
        if (isset($_files[$itemtype][$event][$module]))
            return $_files[$itemtype][$event][$module];
        $loaded = false;
        
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
                // import the file (raises exception if file not found) 
                sys::import("modules.{$module}.class.{$type}.{$filename}");
                $classname = ucfirst($module) . $event . $suffix;
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
        if (isset(self::$subjects[$subjecttype][$event]))
            unset(self::$subjects[$subjecttype][$event]);
        return true;
    }
    
    public static function unregisterObserver($event, $module)
    {
        $observertype = static::getObserverType();
        if (!self::unregister($event, $module, $observertype)) return;
        if (isset(self::$observers[$observertype][$event][$module]))
            unset(self::$observers[$observertype][$event][$module]);
        return true;
    }

    
    private static function unregister($event, $module, $itemtype)
    {
        // validate input        
        $invalid = array();
        if (empty($event) || !is_string($event) || strlen($event) > 255)
            $invalid[] = 'event';
        if (empty($module) || (!is_string($module) && !is_numeric($module)) ) {
            $invalid[] = 'module';
        } else {
            if (is_numeric($module)) {
                $module_id = $module;
            } else {
                $module_id = xarMod::getRegID($module);
            }
            if (!empty($module_id))
                $modinfo = xarMod::getInfo($module_id);
            if (empty($modinfo))
                $invalid[] = 'module';
        }
        if (empty($itemtype) || !is_numeric($itemtype)) {
            $invalid[] = 'itemtype';
        }
        if (!empty($invalid)) {
            $vars = array(join(', ', $invalid), 'register', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }
                
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $emstable = $tables['eventsystem'];
                
        // remove event item
        $query = "DELETE FROM $emstable WHERE event = ? AND module_id = ? AND itemtype = ?";
        $bindvars = array($event, $module_id, $itemtype);
        $result = &$dbconn->Execute($query,$bindvars);
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
        static::getSubjects();
        $subjecttype = static::getSubjectType();
        if (!isset(self::$subjects[$subjecttype][$event]))
            self::$subjects[$subjecttype][$event] = array();
        return self::$subjects[$subjecttype][$event];
    }

    /**
     * Load all subjects from db for current subject type
     * We only ever do this once per page request, results are cached
     * @param none
     * @return array containing subjects, indexed by event name
     * used internally by the event system, must not be overloaded 
    **/
    final public static function getSubjects()
    {
        $subjecttype = static::getSubjectType();
        if (isset(self::$subjects[$subjecttype]))
            return self::$subjects[$subjecttype];
        
        // initialize the cache (we only ever run this query once per subject type)
        self::$subjects[$subjecttype] = array();

        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        $query = "SELECT es.id, es.event, es.module_id, es.area, es.type, es.func, es.itemtype, es.scope,
                         ms.name
                  FROM $etable es, $mtable ms";
        // get subjects for valid, active modules only 
        $where[] = "es.module_id = ms.regid";
        $where[] = "ms.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE;
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
            list($id, $event, $module_id, $area, $type, $func, $itemtype, $scope, $module) = $result->fields;
            // cache results            
            self::$subjects[$subjecttype][$event] = array(
                'id' => $id,
                'event' => $event,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
                'scope' => $scope,
            );
        };
        $result->close();
        // return cached results   
        return self::$subjects[$subjecttype];
    }

    /**
     * Get all observers of an event subject from db
     *
     * @param object $subject ixarEventSubject
     * @return array containing subject observers
    **/
    public static function getObservers(ixarEventSubject $subject)
    {
        $event = $subject->getSubject();
        $info = static::getSubject($event);
        $subjecttype = static::getSubjectType();
        if (empty($info) || $info['itemtype'] != $subjecttype) 
            return array();
        $observertype = static::getObserverType();
        
        if (isset(self::$observers[$observertype][$event]))
            return self::$observers[$observertype][$event];
        
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        //$htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        // get all registered observers to registered subjects
        $query = "SELECT o.id, o.event, o.module_id, mo.name, o.area, o.type, o.func, o.itemtype
                  FROM $etable o, $etable s, $mtable mo, $mtable ms";
        
        // make sure we only get observers to registered subjects :)  
        $where[] =  "o.event = s.event";
        
        // only get subjects belonging to a registered module
        $where[] = "ms.regid = s.module_id";
         // only get subjects of active modules
        $where[] = "ms.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE; 
        // only get subjects for the current subject itemtype
        $where[] =  "s.itemtype = ?";
        $bindvars[] = $subjecttype;
        // only get observers belonging to a registered module
        $where[] = "mo.regid = o.module_id";
        // only get observers of active modules
        $where[] = "mo.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE;  
        // only get observers for the current observer itemtype
        $where[] = "o.itemtype = ?";
        $bindvars[] = $observertype;
        // only observers of this event subject
        $where[] = "s.event = ?";
        $bindvars[] = $event;

        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        $query .= " ORDER BY mo.name ASC, o.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        $obs = array();
        while($result->next()) {
            list($id, $evt, $module_id, $module, $area, $type, $func, $itemtype) = $result->fields;
            // @todo: cache these effectively
            self::$observers[$itemtype][$evt][$module] = array(
                'id' => $id,
                'event' => $evt,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
            );
        };
        if (!isset(self::$observers[$observertype][$event])) 
            self::$observers[$observertype][$event] = array();

        return self::$observers[$observertype][$event];  
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
        $query = "SELECT eo.id, eo.event, eo.module_id, eo.area, eo.type, eo.func, eo.itemtype,
                         mo.name,
                         es.scope
                  FROM $etable eo, $etable es, $mtable mo, $mtable ms";
        // get only observers with a corresponding subject registered
        $where[] = "eo.event = es.event";
        // make sure they belong to a valid module
        $where[] = "eo.module_id = mo.regid";
        // make sure they belong to an active module
        $where[] = "mo.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE;
        $where[] = "ms.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE;
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
            list($id, $evt, $module_id, $area, $type, $func, $itemtype, $modname, $scope) = $result->fields;
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
                'scope' => $scope,
            );
        } 
        return $_modules[$observertype];
    }
     
}

interface ixarEvents
{
    public static function getSubjectType();
    public static function getObserverType();
    public static function getObservers(ixarEventSubject $subject);
    public static function registerSubject($event,$scope,$module,$area,$type,$func);
    public static function register($event,$module,$area,$type,$func,$itemtype,$scope);    
    public static function registerObserver($event,$module,$area,$type,$func);
    public static function unregisterSubject($event,$module);    
    public static function unregisterObserver($event,$module);
    public static function notify($event, $args);
    public static function getSubject($event);
    public static function getSubjects();
    public static function fileLoad($info);
}

?>