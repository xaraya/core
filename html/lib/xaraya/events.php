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
    const SUBJECT_TYPE = 1;
    const OBSERVER_TYPE = 2;
    
    // Cached event subjects and observers 
    protected static $subjects;
    protected static $observers;

    public static function init(&$args)
    {
        // Register tables this subsystem uses
        $tables = array('eventsystem' => xarDB::getPrefix() . '_eventsystem');
        xarDB::importTables($tables);
        return true;
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
        $info = self::getSubject($event);
        if (empty($info)) return;
        
        
        // get modinfo for specified event
        $modinfo = xarMod::getInfo($info['module_id']);
        if (!$modinfo) return;
        
        // make sure module is available
        $modname = $modinfo['name'];
        if (!xarMod::isAvailable($modname)) return;
        
        // Load event subject class file 
        if (!self::loadFile($info['event'], $info['module_id'], xarEvent::SUBJECT_TYPE)) return;
        
        // define class (loadFile already checked it exists) 
        $classname = ucfirst($modname) . $info['event'] . "Subject";

        // create subject instance, passing $args from caller
        $subject = new $classname($args);
         
        // Each subject is responsible for returning its own array of observer items   
        $observers = $subject->getObservers();
        
        // Attach observers to subject
        if (!empty($observers)) { 
            // Each observer must be an array containing eventsystem data (id, event, module_id, itemtype)
            foreach ($observers as $obs) {
                // Load event observer class file 
                if (!self::loadFile($obs['event'], $obs['module_id'], xarEvent::OBSERVER_TYPE)) continue;
                // define class                
                $obsclass = ucfirst($modname) . $obs['event'] . "Observer";
                // attach observer to subject                
                $subject->attach(new $obsclass());
            }
            // notify subject observers and capture the response
            $response = $subject->notify();
        } else {
            // no observers, event was a success
            $response = true;
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
    public static function registerSubject($event, $module)
    {
        return self::register($event, $module, xarEvent::SUBJECT_TYPE);                
    }    
    
    public static function registerObserver($event, $module)
    {
        return self::register($event, $module, xarEvent::OBSERVER_TYPE);
    }    
    
    /**
     * event registration function
     * used internally by registerSubject and registerObserver methods 
     *
     * @access private
     * @param string $event name of the event to observer or listener, required
     * @param mixed $module either string name of module, or int regid of module
     * @param int $itemtype id of event itemtype either SUBJECT_TYPE or OBSERVER_TYPE
     *
     * @throws BadParameterException, DBException, DuplicateEventException
     * @returns bool, true on scuccess
    **/
    private static function register($event, $module, $itemtype)
    {
        // check module subsystem is up before running
        if (!class_exists('xarMod')) return;
        
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
        if ($itemtype != xarEvent::SUBJECT_TYPE && $itemtype != xarEvent::OBSERVER_TYPE) {
            $invalid[] = 'itemtype';
        } elseif ($itemtype == xarEvent::SUBJECT_TYPE) {
            // for subjects, duplicates are not allowed, see if the subject already exists
            $subject = self::getSubject($event);
            // if we have a subject, see if it belongs to the module attempting to register this event
            if (!empty($subject)) {
                if ($subject['module_id'] != $module_id) {
                    // subject name is already registered by another module
                    // @TODO: throw new DuplicateEventRegistrationException($event);
                    $invalid[] = 'duplicate event subject';
                } else {
                    // subject already registered for this module, just return the id
                    return $subject['id'];
                }
            }
        } else {
            // for observers, see if already registered (duplicate events are allowed)
            $observer = self::getObserver($event, $module_id);
            if (!empty($observer) && $observer['module_id'] == $module_id) {
                // observer already registered for this module, just return the id
                return $observer['id'];
            }
        }
        if (!empty($invalid)) {
            // @TODO: throw new EventRegistrationException($event);
            $vars = array(join(', ', $invalid), 'register', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }
        
        // attempt file load
        if (!self::loadFile($event, $module_id, $itemtype))
            // @TODO: throw new EventRegistrationException($event);
            return;
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
                  itemtype
                  )
                  VALUES (?,?,?,?)";

        $bindvars = array();
        $bindvars[] = $nextId;
        $bindvars[] = $event;
        $bindvars[] = $module_id;
        $bindvars[] = $itemtype;

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        
        $id = $dbconn->PO_Insert_ID($emstable, 'id');
        if (empty($id)) return;
        if ($itemtype == xarEvent::SUBJECT_TYPE) {
            // add event subject to the static cache 
            self::$subjects[$event] = array(
                'id' => $id, 
                'event' => $event, 
                'module_id' => $module_id, 
                'itemtype' => $itemtype,
            );
        }
        return $id;
    }

    public static function unregisterSubject($event, $module)
    {
        return self::unregister($event, $module, xarEvent::SUBJECT_TYPE);
    }
    
    public static function unregisterObserver($event, $module)
    {
        return self::unregister($event, $module, xarEvent::OBSERVER_TYPE);
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
        if ($itemtype != xarEvent::SUBJECT_TYPE && $itemtype != xarEvent::OBSERVER_TYPE) {
            $invalid[] = 'itemtype';
        }
        if (!empty($invalid)) {
            // @TODO: throw new EventRegistrationException($event);
            $vars = array(join(', ', $invalid), 'register', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }
        // make sure the item exists
        if ($itemtype == xarEvent::SUBJECT_TYPE) {
            $info = self::getSubject($event);
        } else {
            $info = self::getObserver($event, $module_id);
        }
        // already gone, we're done...
        if (!$info) return true;        
        
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $emstable = $tables['eventsystem'];
                
        // remove event item
        $query = "DELETE FROM $emstable WHERE id = ?";
        $bindvars[] = $info['id'];
        $result = &$dbconn->Execute($query,$bindvars);
        if (!$result) return;
        if ($itemtype == xarEvent::SUBJECT_TYPE) {                
            if (isset($self::$subjects[$event]))
                unset($self::$subjects[$event]);
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
        // Load cache if it isn't already loaded 
        if (!isset(self::$subjects))
            self::getSubjects();
        // Find the cached event and return it
        if (isset(self::$subjects[$event]))
            return self::$subjects[$event];
        return false;
    }

    /**
     * Get all subjects from db
     * @param none
     * @return array containing subjects, indexed by event name
    **/
    public static function getSubjects()
    {
        // see if the cache was already set
        if (isset(self::$subjects))
            // return cached event subjects
            return self::$subjects;
        // first (only) run, get event subjects from db
        $args = array('itemtype' => xarEvent::SUBJECT_TYPE);
        $subjects = self::getItems($args);
        foreach ($subjects as $subject) {
            $event = $subject['event'];
            // add each event subject to the static cache
            self::$subjects[$event] = $subject;
        }
        // return cached event subjects
        return self::$subjects;
    }

    /**
     * Get db info for an event observer
     * 
     * @param string $event name of event subject, required
     * @param int $module_id, id of module observer belongs to
     * @return mixed array of observer info or bool false    
    **/   
    public static function getObserver($event, $module_id)
    {
        $args = array('event' => $event, 'module_id' => $module_id, 'itemtype' => xarEvent::OBSERVER_TYPE);
        $items = self::getItems($args);
        if (count($items) <> 1) return;
        return reset($items);
    }
    /**
     * Get all observers of an event subject from db
     *
     * @param string $event name of event subject, required
     * @return array containing specified event observers
    **/
    public static function getObservers($event)
    {
        if (isset(self::$observers[$event]))
            return self::$observers[$event];
        $args = array('event' => $event, 'itemtype' => xarEvent::OBSERVER_TYPE);
        return self::$observers[$event] = self::getItems($args);
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
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $bindvars = array();
        $where = array();
        $emstable = $tables['eventsystem'];
        $query = "SELECT id, event, module_id, itemtype FROM $emstable";
        
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
            list ($id, $event, $module_id, $itemtype) = $result->fields;
            $items[] = array(
                'id' => $id,
                'event' => $event,
                'module_id' => $module_id,
                'itemtype' => $itemtype,
            );
        }
        $result->Close();
        return $items;
   
    }

/**
 * Class file loader
**/
    public static function loadFile($event, $module, $itemtype)
    {
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
        if ($itemtype != xarEvent::SUBJECT_TYPE && $itemtype != xarEvent::OBSERVER_TYPE) {
            $invalid[] = 'itemtype';
        }
        if (!empty($invalid)) {
            $vars = array(join(', ', $invalid), 'loadFile', 'xarEvent');
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }      

        static $loaded = array();
        $key = "{$event}:{$module_id}";
        if (isset($loaded[$itemtype][$key]))
            return $loaded[$itemtype][$key];
            
        // class loader
        $isloaded = false;          
        $modname = $modinfo['name'];
        $eventtype = $itemtype == xarEvent::SUBJECT_TYPE ? 'subject' : 'observer';      
        $filename = strtolower($event);
        $filepath = sys::code() . "modules/{$modname}/class/event{$eventtype}s/{$filename}.php";
        // check file exists
        if (file_exists($filepath)) {
            // include file                
            include_once($filepath);
            // check class exists
            $classname = ucfirst($modname) . $event . ucfirst($eventtype);
            if (class_exists($classname)) 
                $isloaded = true; 
        }
        // one file load complete, cache and return
        return $loaded[$itemtype][$key] = $isloaded;
                
    }
     
}


/**
 * Event Subject Interface
 *
 * All Event Subjects must implement this
**/
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