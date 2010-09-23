<?php
/**
 * Event Messaging System
**/
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
 * @package events
 */
class EventRegistrationException extends RegistrationExceptions
{
    protected $message = 'The event "#(1)" is not properly registered';
}
class DuplicateEventRegistrationException extends EventRegistrationException
{
    protected $message = 'Unable to register event subject "#(1)", already registered by another module';
}

class xarEvent extends Object 
{
    // Event system itemtypes 
    const SUBJECT_TYPE       = 1;   // System event subjects, handles OBSERVER_TYPE events
    const OBSERVER_TYPE      = 2;   // System event observers
    
    // Cached event subjects and observers
    // @TODO: evaluate caching
    protected static $subjects;
    protected static $observers;
    
    protected static $_itemtypes;

    public static function init(&$args)
    {
        // Register tables this subsystem uses
        $tables = array('eventsystem' => xarDB::getPrefix() . '_eventsystem');
        xarDB::importTables($tables);
        return true;
    }
    public static function getSubjectType()
    {
        return xarEvent::SUBJECT_TYPE;
    }
    
    public static function getObserverType()
    {
        return xarEvent::OBSERVER_TYPE;
    }
    
    /**
     * public event notifier function
     *
     * @param string event name of event subject, required
     * @param array $args arguments to pass to subject, optional
     * @return mixed response from subject notify method
    **/
    public static function notify($event, $args=array())
    {
        // check module subsystem is up before running (SessionCreate is raised during install)
        if (!class_exists('xarMod')) return;

        // get info for specified event
        $info = static::getSubject($event);
        if (empty($info)) return;
       
        // Attempt to load subject file 
        try {
            // file load takes care of validation for us 
            if (!self::fileLoad($info)) return; 
        } catch (Exception $e) {
            // @TODO: debug switch (cfr. blocks) to raise exceptions on demand
            return;
        }
        // @TODO: wrap this in a try / catch 
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
                        try {
                            if (!self::fileLoad($obs)) continue;
                        } catch (Exception $e) {
                            continue;
                        }
                        // @TODO: wrap this in a try / catch
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
                                sys::import("modules.modules.class.eventobservers.apiclass");
                                $obsclass = "ModulesApiClassObserver";
                                $subject->attach(new $obsclass($obs));
                            break;
                            case 'gui':
                                // wrap gui function in guiclass observer
                                sys::import("modules.modules.class.eventobservers.guiclass");
                                $obsclass = "ModulesGuiClassObserver";
                                $subject->attach(new $obsclass($obs));
                            break;                            
                        }       
                    }
                }
                $method = !empty($info['func']) ? $info['func'] : 'notify';
                // always notify the subject, even if there are no observers
                $response = $subject->$method();
            break;
            case 'api':
                // fileLoad should have made sure file/func exists, but just in case...
                try {
                    $response = xarMod::apiFunc($module, $info['type'], $info['func'], $args);
                } catch (Exception $e) {
                    $response = false;
                }
            break;
            case 'gui':
                // not allowed in event subjects
                default:                
                $response = false;
            break;          
        }
        
        // now notify Event subject observers that an event was just raised
        // (these are generic listeners that observe every event raised)
        // We only do this if this isn't the generic Event itself...
        if ($event != 'Event') 
            xarEvent::notify('Event', $info);

        // return the response
        return $response;
        
    }

    /**
     * public event registration functions
     *
    **/    
    public static function registerSubject($event,$module,$area='class',$type='eventsubjects',$func='notify')
    {
        return self::register($event, $module, $area, $type, $func, static::getSubjectType());
    }    
    
    public static function registerObserver($event,$module,$area='class',$type='eventobservers',$func='notify')
    {       
        return self::register($event, $module, $area, $type, $func, static::getObserverType());
    }    
    
    /**
     * event registration function
     * used internally by registerSubject and registerObserver methods 
     *
     * @access protected
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
     * xarEvent::registerSubject('MyEvent', 'base', 'class', 'eventsubject', 'notify');
     * Note: by using defaults for area, type and func as above we could have just written 
     * xarEvent::registerSubject('MyEvent', 'base);
     * BaseMyEventObserver::notify() in file /base/class/baseobserver/myevent.php
     * xarEvent::registerSubject('OtherEvent', 'roles', 'api', 'user', 'otherevent');
     * xarMod::apiFunc('roles', 'user', 'otherevent');
    **/
    
    protected static function register($event,$module,$area='class',$type='eventobservers',$func='notify', $itemtype) 
    {
        // check module subsystem is up before running
        if (!class_exists('xarMod')) return;

        $info = array(
            'event' => $event,
            'module' => $module,
            'area' => $area,
            'type' => $type,
            'func' => $func,
            'itemtype' => $itemtype,
        );        
                      
        // file load takes care of validation, any invalid input throws an exception 
        if (!self::fileLoad($info)) return;
        
        $module_id = !is_numeric($module) ? xarMod::getRegID($module) : $module; 
        $info['module_id'] = $module_id;

        // check if item already exists
        $exists = static::getItem($info);
        if (!empty($exists)) return $exists['id'];
        
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
                  itemtype
                  )
                  VALUES (?,?,?,?,?,?,?)";

        $bindvars = array();
        $bindvars[] = $nextId;
        $bindvars[] = $event;
        $bindvars[] = $module_id;
        $bindvars[] = $area;
        $bindvars[] = $type;
        $bindvars[] = $func;
        $bindvars[] = $itemtype;

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        
        $id = $dbconn->PO_Insert_ID($emstable, 'id');
        if (empty($id)) return;
        $info['id'] = $id;
 
        self::$_itemtypes[$itemtype][$event] = $info;
        
        return $id;
    }
    
    public static function fileLoad($args)
    {
        extract($args);
        
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
        if (empty($modinfo) || !xarMod::isAvailable($modinfo['name']))
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
        if (isset($_files[$event][$itemtype][$module]))
            return $_files[$event][$itemtype][$module];
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
                // type is required for api and gui funcs 
                if (empty($type) || !is_string($type))
                    throw new BadParameterException('type');
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
        return $_files[$event][$itemtype][$module] = $loaded;
    }
        

    public static function unregisterSubject($event, $module)
    {
        return self::unregister($event, $module, static::getSubjectType());
    }
    
    public static function unregisterObserver($event, $module)
    {
        return self::unregister($event, $module, static::getObserverType());
    }

    
    private static function unregister($event, $module, $itemtype)
    {
        // validate input        
        $invalid = array();
        if (empty($event) || !is_string($event) || strlen($event) > 255)
            $invalid[] = 'event subject';
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
        
        $exists = static::getItem($info);
        // already gone, we're done...
        if (empty($exists)) return true;        
        
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $emstable = $tables['eventsystem'];
                
        // remove event item
        $query = "DELETE FROM $emstable WHERE id = ?";
        $bindvars[] = $info['id'];
        $result = &$dbconn->Execute($query,$bindvars);
        if (!$result) return;
        if ($itemtype == static::getSubjectType()) {                
            if (isset(self::$_itemtypes[$itemtype][$event]))
                unset(self::$_itemtypes[$itemtype][$event]);
        }
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
    **/
    public static function getSubject($event)
    {
        static::getSubjects();
        $itemtype = static::getSubjectType();
        if (isset(self::$_itemtypes[$itemtype][$event]))
            return self::$_itemtypes[$itemtype][$event];
        return false;
    }

    /**
     * Get all subjects from db
     * @param none
     * @return array containing subjects, indexed by event name
    **/
    public static function getSubjects()
    {
        $itemtype = static::getSubjectType();
        if (isset(self::$_itemtypes[$itemtype]))
            return self::$_itemtypes[$itemtype];
        // first (only) run, get event subjects from db
        $args = array('itemtype' => $itemtype);
        $subjects = static::getItems($args);
        if (!isset(self::$_itemtypes[$itemtype]))
            self::$_itemtypes[$itemtype] = array();
        foreach ($subjects as $subject) {
            $event = $subject['event'];
            // add each event subject to the static cache
            self::$_itemtypes[$itemtype][$event] = $subject;
        }
        // return cached event subjects
        return self::$_itemtypes[$itemtype];
    }

    /**
     * Get db info for an event observer
     * 
     * @param string $event name of event subject, required
     * @param int $module_id, id of module observer belongs to
     * @return mixed array of observer info or bool false    
    **/   
    public static function getObserver($event, $module)
    {
        $itemtype = static::getObserverType();
        $module_id = is_numeric($module) ? $module : xarMod::getRegID($module);
        $args = array('event' => $event, 'module_id' => $module_id, 'itemtype' => $itemtype);
        return static::getItem($args);
    }
    /**
     * Get all observers of an event subject from db
     *
     * @param mixed $event string, name of event subject 
     *                     object ixarEventSubject object with event name as subject property
     *                     array subject info containing event name
     *                     optional, default empty (all observers)
     * @return array containing specified event observers
    **/
    public static function getObservers($event=null)
    {
        if (is_object($event)) {
            // notify method passes whole object
            $subject = $event->getSubject();
        } elseif (is_string($event)) {
            // for convenience, specifying the name of the event is supported
            $subject = $event;
        } elseif (is_array($event)) {
            // passing an array containing the event is also supported
            if (isset($event['event'])) {
                $subject = $event['event'];
            }
            // @TODO: optionally filter by subject and observer type ?
            // NOTE: default uses current static bindings from calling class
            if (isset($event['subjecttype']) && isset($event['observertype'])) {
                // types come in pairs
                $subjecttype = $event['subjecttype'];
                $observertype = $event['observertype'];
            }
            // optionally filter by specific subject module
            if (isset($event['subject_id'])) {
                $subject_id = $event['subject_id'];
            }
            // optionally filter by specific observer module
            if (isset($event['observer_id'])) {
                $observer_id = $event['observer_id'];
            }
        } 
        if (empty($subjecttype) || empty($observertype)) {
            // types come in pairs
            $subjecttype = static::getSubjectType();     
            $observertype = static::getObserverType();
        }
        
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
      
        // optionally filter by event subject        
        if (!empty($subject)) {
            $where[] = "s.event = ?";
            $bindvars[] = $subject;
        }
        // optionally filter by subject module
        if (!empty($subject_id)) {
            $where[] = "s.module_id = ?";
            $bindvars[] = $subject_id;
        }
        // optionally filter by observer module
        if (!empty($observer_id)) {
            $where[] = "o.module_id = ?";
            $bindvars[] = $observer_id;
        }
        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        $query .= " ORDER BY mo.name ASC, o.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        $obs = array();
        while($result->next()) {
            list($id, $event, $module_id, $module, $area, $type, $func, $itemtype) = $result->fields;
            // @todo: cache these effectively 
            $obs[] = array(
                'id' => $id,
                'event' => $event,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
            );
        };
        $result->close();
        return $obs;     
    }
    public static function getItem($args)
    {
        $items = static::getItems($args);
        if (count($items) <> 1) return;
        return reset($items);
    }

    /**
     * Get event items from the db
     * 
     * @param int id id of the event item, optional
     * @param string event name of the event, optional
     * @param int module_id id of the module item belongs to, optional
     * @param int itemtype eventsystem itemtype (xarEvent::SUBJECT_TYPE|xarEvent::OBSERVER_TYPE), optional
     * @param int startnum offset to begin at, optional, default 0
     * @param int numitems number of records to return, optional, default -1 (all) 
     * @return array of event items
    **/
    public static function getItems(Array $args=array())
    {
        if (!isset($args['module_id']) && isset($args['module'])) {
            $args['module_id'] = is_numeric($args['module']) ? $args['module'] : xarMod::getRegID($args['module']);
        }

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $where = array();
        $emstable = $tables['eventsystem'];
        $query = "SELECT id, event, module_id, area, type, func, itemtype FROM $emstable";
        
        if (!empty($args['id']) && is_numeric($args['id'])) {
            $where[] = 'id = ?';
            $bindvars[] = $args['id'];
        }
        if (!empty($args['event']) && is_string($args['event'])) {
            $where[] = 'event = ?';
            $bindvars[] = $args['event'];
        }
        if (!empty($args['module_id']) && is_numeric($args['module_id'])) {
            $where[] = 'module_id = ?';
            $bindvars[] = $args['module_id'];
        } 
        if (!empty($args['itemtype']) && is_numeric($args['itemtype'])) {
            $where[] = 'itemtype = ?';
            $bindvars[] = $args['itemtype'];
        }
        
        if (!empty($where)) {
            $query .= " WHERE " . join(" AND ", $where);
        }
        
        $numitems = empty($args['numitems']) || !is_numeric($args['numitems']) ? -1 : $args['numitems'];
        $startnum = empty($args['startnum']) || !is_numeric($args['startnum']) ? 0 : $args['startnum']-1;

        $result = $dbconn->SelectLimit($query, $numitems, $startnum, $bindvars);
        if (!$result) return;

        $items = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list ($id, $event, $module_id, $area, $type, $func, $itemtype) = $result->fields;
            $items[] = array(
                'id' => $id,
                'event' => $event,
                'module_id' => $module_id,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
            );
        }
        $result->Close();
        return $items;
   
    }
     
}
interface ixarEventSubject
{
    public function notify();
    public function attach(ixarEventObserver $observer);
    public function detach(ixarEventObserver $observer);
    public function getObservers();
}
/**
 * Event Observer Interface
 *
 * All Event Observers must implement this
**/
interface ixarEventObserver
{
    public function notify(ixarEventSubject $subject);
}

?>