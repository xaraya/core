<?php
/**
 * (Module) Hooks handling subsystem - moved from modules to hooks for (future) clarity
 * @todo Hooks are currently linked with modules & itemtypes, not objects
 * @checkme Control actions further and e.g. automatically detect & call hook actions in various places ?
 * @checkme Replace 'module' with 'config' scope to indicate that we actually configure hooks for module itemtypes, objects, etc. there ?
 * @todo <chris> review the above todo's and checkme's
 * @package hooks
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 */
class xarHooks extends xarEvents
{
    // unique event system itemtype ids for storage/retrieval/actioning in the event system 
    const HOOK_SUBJECT_TYPE  = 3;    
    const HOOK_OBSERVER_TYPE = 4;
/**    
 * required functions, provide event system with late static bindings for these values
**/
    public static function getSubjectType()
    {
        return xarHooks::HOOK_SUBJECT_TYPE;
    }    
    public static function getObserverType()
    {
        return xarHooks::HOOK_OBSERVER_TYPE;
    }
    /**
     * public event registration functions
     *
    **/    
    public static function registerSubject($event,$module,$area='class',$type='hooksubjects',$func='notify')
    {
        return xarHooks::register($event, $module, $area, $type, $func, xarHooks::HOOK_SUBJECT_TYPE);
    }    
    
    public static function registerObserver($event,$module,$area='class',$type='hookobservers',$func='notify')
    {       
        return xarHooks::register($event, $module, $area, $type, $func, xarHooks::HOOK_OBSERVER_TYPE);
    } 

    // this function is called when an event is raised (hook called)
    // it returns all modules hooked to the caller module (+ itemtype) that raised the event
    // NOTE: This function is called by the event module, modify with caution
    public static function getObservers($event=null)
    {
        if (is_object($event)) {
            // notify method passes whole object
            $subject = $event->getSubject();
            // name and itemtype of caller is always in extrainfo
            $extrainfo = $event->getExtrainfo();
            $subject_id = xarMod::getRegID($extrainfo['module']);
            $subject_itemtype = $extrainfo['itemtype'];
        } elseif (is_string($event)) {
            // for convenience, specifying the name of the event is supported
            $subject = $event;
        } elseif (is_array($event)) {
            // passing an array containing the event is also supported
            if (isset($event['event'])) {
                $subject = $event['event'];
            }
            // optionally filter by specific subject module
            if (isset($event['module'])) {
                $subject_id = xarMod::getRegID($event['module']);
            }
            if (isset($event['itemtype'])) {
                $subject_itemtype = $event['itemtype'];
            }
        } 
        
        // types come in pairs
        $subjecttype = static::getSubjectType();     
        $observertype = static::getObserverType();
        
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = array();
        $where = array();
        $query = "SELECT eo.id, eo.event, eo.module_id, eo.area, eo.type, eo.func, eo.itemtype,
                         mo.name
                  FROM $htable h, $etable eo, $mtable mo";
        // only get observers for the current observer itemtype
        $where[] =  "eo.itemtype = ?";
        $bindvars[] = $observertype;        
        // only get observers of this event        
        $where[] = "eo.event = ?";
        $bindvars[] = $subject;
        // only modules hooked to this subject
        $where[] = "h.subject = ?";
        $bindvars[] = $subject_id;
        $where[] = "eo.module_id = h.observer";
        // only get observers belonging to a registered module
        $where[] = "eo.module_id = mo.regid";
        // only get observers of active modules
        $where[] = "mo.state = ?";
        $bindvars[] = XARMOD_STATE_ACTIVE;  
        if (!empty($subject_itemtype)) {
            $where[] = "(h.itemtype = ? OR h.itemtype = ?)";
            $bindvars[] = $subject_itemtype;
            $bindvars[] = 0;
        } else {
            $where[] = "h.itemtype = ?";
            $bindvars[] = 0;
        }        
        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        // @TODO: allow ordering ?
        $query .= " ORDER BY mo.name ASC, eo.event ASC";                  
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        $obs = array();
        while($result->next()) {
            list($id, $event, $module_id, $area, $type, $func, $itemtype, $module) = $result->fields;
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
    
    
/**
 * Hook system functions
 * @TODO: move these to xarHookss when that class is refactored
**/
    /**
     * Attach (hook) a hook module (observer) to a module (subject) (+ itemtype)
    **/ 
    public static function attach($observer, $subject, $itemtype=null)
    {
        // Argument check
        if (empty($observer)) 
            throw new EmptyParameterException('observer');
        if (empty($subject))   
            throw new EmptyParameterException('subject');
        if (!empty($itemtype) && !is_numeric($itemtype)) 
            throw new BadParameterException('itemtype');
        
        $observer_id = xarMod::getRegID($observer);
        if (empty($observer_id)) return;
        $subject_id = xarMod::getRegID($subject);
        if (empty($subject_id)) return;
        
        if (empty($itemtype)) $itemtype = 0;
        
        if (xarHooks::isAttached($observer, $subject, $itemtype)) return true;
        
        // when hooking to itemtype 0 (all items) we need to remove hooks to distinct itemtypes
        if ($itemtype === 0) {
            // remove all hooks from this observer to this subject
            if (!xarHooks::detach($observer, $subject, -1)) return;
        }
        
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $htable = $xartable['hooks'];
        // Insert hook
        try {
            $dbconn->begin();
            $query = "INSERT INTO $htable
                     (
                      observer,
                      subject,
                      itemtype
                     )
                     VALUES (?,?,?)";
            $bindvars = array($observer_id, $subject_id, $itemtype);
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;       
    }
    /**
     * Detach (unhook) a hook module (observer) from a module (subject) (+ itemtype)
    **/
    public static function detach($observer, $subject, $itemtype=null)
    {
        // Argument check
        if (empty($observer)) 
            throw new EmptyParameterException('observer');
        if (empty($subject))   
            throw new EmptyParameterException('subject');
        if (!empty($itemtype) && !is_numeric($itemtype)) 
            throw new BadParameterException('itemtype');
        
        $observer_id = xarMod::getRegID($observer);
        if (empty($observer_id)) return;
        $subject_id = xarMod::getRegID($subject);
        if (empty($subject_id)) return;
        
        if (empty($itemtype)) $itemtype = 0;
                
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $htable = $xartable['hooks'];
        // Delete hook
        try {
            $dbconn->begin();
            $query = "DELETE FROM $htable
                      WHERE observer = ? AND subject = ?";
            $bindvars = array($observer_id, $subject_id);
            // itemtype -1 = detach from all subject itemtypes 
            if ($itemtype !== -1) {
                $query .= " AND itemtype = ?";
                $bindvars[] = $itemtype;
            }
            $dbconn->Execute($query, $bindvars);
            $dbconn->commit();                
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }
    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
    **/
    public static function isAttached($observer, $subject, $itemtype=null)
    {
        // Argument check
        if (empty($observer)) 
            throw new EmptyParameterException('observer');
        if (empty($subject))   
            throw new EmptyParameterException('subject');
        if (!empty($itemtype) && !is_numeric($itemtype)) 
            throw new BadParameterException('itemtype');
        
        $observer_id = xarMod::getRegID($observer);
        if (empty($observer_id)) return;
        $subject_id = xarMod::getRegID($subject);
        if (empty($subject_id)) return;
        
        if (empty($itemtype)) $itemtype = 0;
        
        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $htable = $xartable['hooks'];
        $query = "SELECT observer, subject, itemtype
                  FROM $htable
                  WHERE observer = ? AND subject = ?";
        $bindvars = array($observer_id, $subject_id, $itemtype);
        // check if a module is hooked to all (itemtype 0) when an itemtype is specified
        if (!empty($itemtype)) {
            $query .= " AND ( itemtype = ? OR itemtype = ? )";
            $bindvars[] = 0;
        } else {
            $query .= " AND itemtype = ?";
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        if (!$result->next()) return;
        return true;        
    }
    
    // get the list of registered hook modules and their available hook subject observers
    public static function getObserverModules($args=array())
    {
        $args += array(
            'observertype' => xarHooks::HOOK_OBSERVER_TYPE,
            'subjecttype' => xarHooks::HOOK_SUBJECT_TYPE,
        );

        // Get list of hook modules from event system
        // can't use xarHooks::getObservers here since that returns hooked modules for specific events
        $hookmods = xarEvents::getObservers($args);

        // format the list for output
        $hooklist = array();    
        foreach ($hookmods as $mod) {
            $modname = $mod['module'];
            if (!isset($hooklist[$modname])) {
                $modinfo = $mod + xarMod::getInfo($mod['module_id']);
                $hooklist[$modname] = $modinfo;
                $hooklist[$modname]['hooks'] = array();
            }
            $event = $mod['event'];
            $hooklist[$modname]['hooks'][$event] = $mod;
        }
        return $hooklist;
    }
    
    // get the list of modules (subjects) (+itemtypes) a hook module (observer) is hooked to     
    public static function getObserverSubjects($observer, $subject=null)
    {
        // Argument check
        if (empty($observer)) 
            throw new EmptyParameterException('observer');        
        
        $observer_id = xarMod::getRegID($observer);
        if (empty($observer_id)) return;

        if (!empty($subject)) {
            $subject_id = xarMod::getRegId($subject);
            if (empty($subject_id)) return;
        }

        // Get database info
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        
        $query = "SELECT ms.name, h.itemtype 
                  FROM $htable h, $mtable mo, $mtable ms
                  WHERE h.observer = ? 
                  AND mo.regid = h.observer
                  AND ms.regid = h.subject";
        $bindvars = array($observer_id);
        if (!empty($subject_id)) {
            $query .= " AND h.subject = ?";
            $bindvars[] = $subject_id;
        }

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) return;
        $subjects = array();
        while($result->next()) {
            list($module, $itemtype) = $result->fields;
            $subjects[$module][$itemtype] = 1;
        }
        return $subjects;              
    }

}



/**
 * Carry out hook operations for module
 *
 * @access public
 * @param hookScope string the scope the hook is called for - 'item', 'module', ...
 * @param hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
 * @param hookId integer the id of the object the hook is called for (module-specific)
 * @param extraInfo mixed extra information for the hook, dependent on hookAction
 * @param callerModName string for what module are we calling this (default = current main module)
 *        Note : better pass the caller module via $extraInfo['module'] if necessary, so that hook functions receive it too
 * @param callerItemType string optional item type for the calling module (default = none)
 *        Note : better pass the item type via $extraInfo['itemtype'] if necessary, so that hook functions receive it too
 * @return mixed output from hooks, or null if there are no hooks
 * @throws BadParameterException
 */
function xarModCallHooks($hookScope, $hookAction, $hookId, $extraInfo = NULL, $callerModName = NULL, $callerItemType = '')
{
    // scope and action are combined to form the name of the hook event
    $event = ucfirst($hookScope) . ucfirst($hookAction);
    $args = array(
        'id' => $hookId,
        'module' => $callerModName,
        'itemtype' => $callerItemType,
        'extrainfo' => $extraInfo,
    );
    // Notify the hook subject (event) observers
    return xarHooks::notify($event, $args);
}

/**
 * Get list of available hooks for a particular module[, scope] and action
 *
 * @access private
 * @param callerModName string name of the calling module
 * @param hookScope string the hook scope
 * @param hookAction string the hook action
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return array of hook information arrays, or null if database error
 * @throws DATABASE_ERROR
 */
// This is the actual function that gets modules hooked to a module (+ itemtype) 
function xarModGetHookList($callerModName, $hookScope, $hookAction, $callerItemType = '')
{
    $event = ucfirst($hookScope) . ucfirst($hookAction);
    $args = array(
        'event' => $event,
        'module' => $callerModName,
        'itemtype' => $callerItemType,
    );
    return xarHooks::getObservers($args);
}

/**
 * Check if a particular hook module is hooked to the current module (+ itemtype)
 *
 * @access public
 * @static modHookedCache array
 * @param hookModName string name of the hook module we're looking for
 * @param callerModName string name of the calling module (default = current)
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return mixed true if the module is hooked
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    return xarHooks::isAttached($hookModName, $callerModName, $callerItemType);
}

/**
 * register a hook function
 *
 * @access public
 * @param hookScope the hook scope
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
 * @param hookModFunc name of the hook function or handler class
 * @return bool true on success
 * @throws BadParameterException
 */
function xarModRegisterHook($hookScope, $hookAction, $hookArea, $hookModName, $hookModType, $hookModFunc)
{
    $event = ucfirst($hookScope) . ucfirst($hookAction);
    return xarHooks::registerObserver($event, $hookModName, $hookArea, $hookModType, $hookModFunc);
}

/**
 * unregister a hook function (deprecated - use unregisterHookModule or the standard deinstall for modules instead)
 *
 * @access public
 * @param hookScope the hook scope
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type ('user' / 'admin' / ... for regular functions, or 'class' for hook call handlers)
 * @param hookModFunc name of the hook function or handler class
 * @return bool true if the unregister call suceeded, false if it failed
 * @throws BadParameterException
 */
function xarModUnregisterHook($hookScope, $hookAction, $hookArea,$hookModName, $hookModType, $hookModFunc)
{
    $event = ucfirst($hookScope) . ucfirst($hookAction);
    return xarHooks::unregisterObserver($event, $hookModName);
}

?>