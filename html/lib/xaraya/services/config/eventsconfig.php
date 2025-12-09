<?php

/**
 * Events Config Service
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Config;

use Xaraya\Services\EventsService;
use ixarMod;
use Query;
use BadParameterException;

/**
 * For documentation purposes only - available via EventsConfig
 */
interface EventsConfigInterface
{
    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify'): mixed;
    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify'): mixed;
    public function unregisterSubject($event, $module): bool;
    public function unregisterObserver($event, $module): bool;
    public function getObserverModules(): array;
}

/**
 * Events Config Service
 * @todo evaluate dependency consequences
 */
class EventsConfig extends EventsService implements EventsConfigInterface
{
    public const SLICE = 'events.config';

    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify'): mixed
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), self::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        // Note: this is overridden in HookedConfig()
        $subjecttype = $this->getSubjectType();
        $info = $this->register($event, $module, $area, $type, $func, $subjecttype, $scope, $classname);
        if (empty($info)) {
            return null;
        }
        return $info['id'];
    }

    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify'): mixed
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), self::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        // Note: this is overridden in HookedConfig()
        $observertype = $this->getObserverType();
        // always empty for observers - used for selective hook observers to a particular subject scope (module/itemtype/item/...)
        $scope = '';
        $info = $this->register($event, $module, $area, $type, $func, $observertype, $scope, $classname);
        if (empty($info)) {
            return null;
        }
        return $info['id'];
    }

    /**
     * event registration function
     * used internally by registerSubject and registerObserver methods
     *
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
     * @return array<string, mixed> on success or empty array
    **/
    final public function register($event, $module, $area = 'class', $type = 'eventobservers', $func = 'notify', $itemtype = 0, $scope = '', $classname = ''): array
    {
        $xar = $this->getServicesClass();

        $module_id = $xar->mod()->getRegID($module);
        // support namespaces in modules (and core someday) - we may pass along $info['classname'] here too
        $info = [
            'event'    => $event,
            'module'   => $module,
            'module_id' => $module_id,
            'area'     => $area,
            'type'     => $type,
            'func'     => $func,
            'itemtype' => $itemtype,
            'classname' => $classname,
            'scope'    => $scope,
        ];

        // file load takes care of validation, any invalid input throws an exception
        if (!$this->fileLoad($info)) {
            return [];
        }

        // keep track of classname as detected in fileLoad() for register()
        if ($area == 'class' && empty($info['classname']) && in_array($info['type'], static::$classtypes)) {
            $classkey = implode(':', [$info['event'], $info['module'], $info['type']]);
            if (!empty(static::$classnames[$classkey])) {
                $info['classname'] = static::$classnames[$classkey];
            }
        }

        // Note: this is overridden in HookedConfig()
        if ($itemtype == $this->getSubjectType()) {
            // see if subject is already registered
            $subject = $this->getSubject($event);
            // event subjects must be unique! (event, module, itemtype)
            if (!empty($subject)) {
                if ($subject['module'] == $module) {
                    // same module, registering same event subject
                    // unregister the event so it can be re-registered ( = updated :) )
                    if (!$this->unregisterSubject($event, $module)) {
                        return [];
                    }
                } else {
                    // CHECKME: doesn't unique mean we can have the same subject for different modules?
                    // oops, that event is already registered by another module, pick a different one!
                    // throw new DuplicateEventRegistrationException($event);
                }
            }
        } elseif ($itemtype == $this->getObserverType()) {
            // event observers don't need to be unique, but each module can
            // only register one observer per event subject
            // unregister the event so it can be re-registered ( = updated :) )
            if (!$this->unregisterObserver($event, $module)) {
                return [];
            }
        }

        // create entry in db
        $dbconn = $xar->db()->getConn();
        $tables = $xar->db()->getTables();
        $bindvars = [];
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

        $bindvars = [];
        $bindvars[] = $event;
        $bindvars[] = $module_id;
        $bindvars[] = $area;
        $bindvars[] = $type;
        $bindvars[] = $func;
        $bindvars[] = $itemtype;
        // support namespaces in modules (and core someday) - we may save $info['classname'] here
        $bindvars[] = $info['classname'];
        $bindvars[] = $scope;

        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return [];
        }

        $id = $dbconn->getLastId($emstable);
        if (empty($id)) {
            return [];
        }
        $info['id'] = $id;
        $info['module_id'] = $module_id;

        return $info;
    }

    public function unregisterSubject($event, $module): bool
    {
        // Note: this is overridden in HookedConfig()
        $subjecttype = $this->getSubjectType();
        if (!$this->unregister($event, $module, $subjecttype)) {
            return false;
        }
        return true;
    }

    public function unregisterObserver($event, $module): bool
    {
        // Note: this is overridden in HookedConfig()
        $observertype = $this->getObserverType();
        if (!$this->unregister($event, $module, $observertype)) {
            return false;
        }
        return true;
    }

    private function unregister($event, $module, $itemtype): bool
    {
        // Validate the input
        $invalid = [];
        if (empty($event) || !is_string($event) || strlen($event) > 255) {
            $invalid[] = 'event';
        }
        if (empty($module) || (!is_string($module) && !is_numeric($module))) {
            $invalid[] = 'module';
        }
        if (empty($itemtype) || !is_numeric($itemtype)) {
            $invalid[] = 'itemtype';
        }
        $xar = $this->getServicesClass();

        // Assemble the query
        $tables = $xar->db()->getTables();
        $q = new Query('DELETE', $tables['eventsystem']);
        $q->eq('itemtype', $itemtype);
        $q->eq('event', $event);

        if (is_numeric($module)) {
            $module_id = $module;
        } else {
            $module_id = $xar->mod()->getRegID($module);
        }
        if (!empty($module_id)) {
            $modinfo = $xar->mod()->getInfo($module_id);
        }
        if (empty($modinfo)) {
            $invalid[] = 'module';
        }
        $q->eq('module_id', $module_id);
        if (!empty($invalid)) {
            $vars = [join(', ', $invalid), 'register', 'xarEvent'];
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }

        // Remove the event item
        $result = $q->run();
        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getObserverModules(): array
    {
        // Note: this is overridden in HookedConfig()
        $observertype = $this->getObserverType();
        static $_modules;
        if (isset($_modules[$observertype])) {
            return $_modules[$observertype];
        }
        $_modules[$observertype] = [];
        $xar = $this->getServicesClass();
        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['modules'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        //$htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = [];
        $where = [];
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
        $bindvars[] = ixarMod::STATE_ACTIVE;
        $where[] = "ms.state = ?";
        $bindvars[] = ixarMod::STATE_ACTIVE;
        // only observers of current observer itemtype
        $where[] = "eo.itemtype = ?";
        $bindvars[] = $observertype;
        // only subjects of current subject itemtype
        $where[] = "es.itemtype = ?";
        // Note: this is overridden in HookedConfig()
        $bindvars[] = static::getSubjectType();

        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        $query .= " ORDER BY mo.name ASC, eo.event ASC";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return [];
        }
        while ($result->next()) {
            [$id, $evt, $module_id, $area, $type, $func, $itemtype, $classname, $modname, $scope] = $result->fields;
            if (!isset($_modules[$observertype][$modname])) {
                $_modules[$observertype][$modname] = [];
            }
            $_modules[$observertype][$modname][$evt] = [
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
            ];
        }
        return $_modules[$observertype];
    }
}
