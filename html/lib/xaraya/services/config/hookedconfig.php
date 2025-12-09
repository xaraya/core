<?php

/**
 * Hooked Config Service
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

use Xaraya\Services\HookedService;
use BadParameterException;
use EmptyParameterException;
use SQLException;

/**
 * For documentation purposes only - available via HookedConfig
 */
interface HookedConfigInterface extends EventsConfigInterface
{
    public function isAttached($observer, $subject, $itemtype = null, $scope = "0"): bool;
    public function attach($observer, $subject, $itemtype = null, $scope = "0"): bool;
    public function detach($observer, $subject, $itemtype = null, $scope = null): bool;
    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'hooksubjects', $func = 'notify'): mixed;
    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'hookobservers', $func = 'notify'): mixed;
    public function unregisterSubject($event, $module): bool;
    public function unregisterObserver($event, $module): bool;
    public function getObserverModules($observer = null): array;
    public function getObserverSubjects($observer, $subject = null, $scope = null): array;
    public function getSubjectObservers($subject, $event, $itemtype = null): array;
}

/**
 * Hooked Config Service
 * Note: we rely on parent::getObserverModules() and $this->register() etc. from EventsConfig() here, but use
 * default $type for registerSubject() and registerObserver() + getSubjectType() & getObjectType() from HookedService()
 * @todo evaluate dependency consequences
 */
class HookedConfig extends EventsConfig implements HookedConfigInterface
{
    public const SLICE = 'hooked.config';

    /**
     * required functions, provide event system with late static bindings for these values
    **/
    public function getSubjectType(): int
    {
        return HookedService::HOOK_SUBJECT_TYPE;
    }

    public function getObserverType(): int
    {
        return HookedService::HOOK_OBSERVER_TYPE;
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     * @return bool true if the observer is attached, false otherwise (for any reason)
    **/
    public function isAttached($observer, $subject, $itemtype = null, $scope = "0"): bool
    {
        // Argument check
        if (empty($observer)) {
            throw new EmptyParameterException('observer');
        }
        if (empty($subject)) {
            throw new EmptyParameterException('subject');
        }
        if (!empty($itemtype) && !is_numeric($itemtype)) {
            throw new BadParameterException('itemtype');
        }
        if (!empty($scope) && !is_numeric($scope) && !is_string($scope)) {
            throw new EmptyParameterException('scope');
        }
        $xar = $this->getServicesClass();

        $observer_id = $xar->mod()->getRegID($observer);
        if (empty($observer_id)) {
            return false;
        }
        $subject_id = $xar->mod()->getRegID($subject);
        if (empty($subject_id)) {
            return false;
        }

        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($scope)) {
            $scope = 0;
        }

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        $query = "SELECT observer, subject, itemtype, scope
                  FROM $htable
                  WHERE observer = ? AND subject = ?";
        $bindvars = [$observer_id, $subject_id, $itemtype, $scope];
        // check if a module is hooked to all (itemtype 0) when an itemtype is specified
        if (!empty($itemtype)) {
            $query .= " AND ( itemtype = ? OR itemtype = ? )";
            $bindvars[] = 0;
        } else {
            $query .= " AND itemtype = ?";
        }
        if (!empty($scope)) {
            $query .= " AND ( scope = ? OR scope = ? )";
            $bindvars[] = '0';
        } else {
            $query .= " AND scope = ?";
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return false;
        }
        if (!$result->next()) {
            return false;
        }
        return true;
    }

    /**
     * Attach (hook) a hook module (observer) to a module (subject) (+ itemtype)
    **/
    public function attach($observer, $subject, $itemtype = null, $scope = "0"): bool
    {
        // Argument check
        if (empty($observer)) {
            throw new EmptyParameterException('observer');
        }
        if (!empty($scope) && !is_numeric($scope) && !is_string($scope)) {
            throw new EmptyParameterException('scope');
        }
        if (empty($subject)) {
            throw new EmptyParameterException('subject');
        }
        if (!empty($itemtype) && !is_numeric($itemtype)) {
            throw new BadParameterException('itemtype');
        }
        $xar = $this->getServicesClass();

        $observer_id = $xar->mod()->getRegID($observer);
        if (empty($observer_id)) {
            return false;
        }
        $subject_id = $xar->mod()->getRegID($subject);
        if (empty($subject_id)) {
            return false;
        }

        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($scope)) {
            $scope = '0';
        }

        if ($this->isAttached($observer, $subject, $itemtype, $scope)) {
            return true;
        }

        // when hooking to itemtype 0 (all items) we need to remove hooks to distinct itemtypes
        if ($itemtype === 0 && $scope === 0) {
            // remove all hooks, all itemtypes, all scopes
            if (!$this->detach($observer, $subject, -1, -1)) {
                return false;
            }
        } elseif ($itemtype === 0) {
            // remove all hooks, all itemtypes, specified scope
            if (!$this->detach($observer, $subject, -1, $scope)) {
                return false;
            }
        } elseif ($scope === 0) {
            // remove all hooks, specified itemtype, all scopes
            if (!$this->detach($observer, $subject, $itemtype, -1)) {
                return false;
            }
        }
        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        // Insert hook
        try {
            $dbconn->begin();
            $query = "INSERT INTO $htable
                     (
                      observer,
                      subject,
                      itemtype,
                      scope
                     )
                     VALUES (?,?,?,?)";
            $bindvars = [$observer_id, $subject_id, $itemtype, $scope];
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
    public function detach($observer, $subject, $itemtype = null, $scope = null): bool
    {
        // Argument check
        if (empty($observer)) {
            throw new EmptyParameterException('observer');
        }
        if (empty($subject)) {
            throw new EmptyParameterException('subject');
        }
        if (!empty($itemtype) && !is_numeric($itemtype)) {
            throw new BadParameterException('itemtype');
        }
        if (!empty($scope) && !is_numeric($scope) && !is_string($scope)) {
            throw new EmptyParameterException('scope');
        }
        $xar = $this->getServicesClass();

        $observer_id = $xar->mod()->getRegID($observer);
        if (empty($observer_id)) {
            return false;
        }
        $subject_id = $xar->mod()->getRegID($subject);
        if (empty($subject_id)) {
            return false;
        }

        if (empty($itemtype)) {
            $itemtype = 0;
        }

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        // Delete hook
        try {
            $dbconn->begin();
            if ($observer == 'all') {
                $query = "DELETE FROM $htable
                          WHERE subject = ?";
                $bindvars = [$subject_id];
            } else {
                $query = "DELETE FROM $htable
                          WHERE observer = ? AND subject = ?";
                $bindvars = [$observer_id, $subject_id];
            }
            // itemtype -1 = detach from all subject itemtypes
            if ($itemtype !== -1) {
                $query .= " AND itemtype = ?";
                $bindvars[] = $itemtype;
            }
            if (isset($scope) && $scope !== -1) {
                $query .= " AND scope = ?";
                $bindvars[] = $scope;
            }
            $dbconn->Execute($query, $bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }

    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'hooksubjects', $func = 'notify'): mixed
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), parent::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        $subjecttype = $this->getSubjectType();
        return $this->register($event, $module, $area, $type, $func, $subjecttype, $scope, $classname);
    }

    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'hookobservers', $func = 'notify'): mixed
    {
        // move classname earlier in params list when they're all classes
        if (in_array(strtolower($classnameOrArea), parent::SUPPORTED_AREAS)) {
            $classname = '';
            $area = $classnameOrArea;
        } else {
            $classname = $classnameOrArea;
            $area = 'class';
        }
        $observertype = $this->getObserverType();
        // always empty for observers - used for selective hook observers to a particular subject scope (module/itemtype/item/...)
        $scope = '';
        return $this->register($event, $module, $area, $type, $func, $observertype, $scope, $classname);
    }

    /**
     * Get the list of hook modules (observers) and their available subject observers (hooks)
     * @param string $observer, name of module supplying hooks
     * @return array<string, mixed>
    **/
    public function getObserverModules($observer = null): array
    {
        $xar = $this->getServicesClass();
        // Get list of hook modules from event system
        $hookmods = parent::getObserverModules();

        // format the list for output
        $hooklist = [];
        foreach ($hookmods as $modname => $hooks) {
            if (!empty($observer) && $modname != $observer) {
                continue;
            }
            $hooklist[$modname] = $xar->mod()->getInfo($xar->mod()->getRegID($modname));
            $hooklist[$modname]['hooks'] = $hooks;
            $hooklist[$modname]['scopes'] = [];
            foreach ($hooks as $event => $info) {
                $scope = $info['scope'];
                if (!isset($hooklist[$modname]['scopes'][$scope][$event])) {
                    $hooklist[$modname]['scopes'][$scope][$event] = $info;
                }
            }
        }
        return $hooklist;
    }

    /**
     * Get the list of modules (subjects) (+itemtypes) a hook module (observer) is hooked to
     * @return array<string, mixed>
    **/
    public function getObserverSubjects($observer, $subject = null, $scope = null): array
    {
        // Argument check
        if (empty($observer)) {
            throw new EmptyParameterException('observer');
        }
        $xar = $this->getServicesClass();

        $observer_id = $xar->mod()->getRegID($observer);
        if (empty($observer_id)) {
            return [];
        }

        if (!empty($subject)) {
            $subject_id = $xar->mod()->getRegID($subject);
            if (empty($subject_id)) {
                return [];
            }
        }

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $query = "SELECT ms.name, h.itemtype, h.scope 
                  FROM $htable h, $mtable mo, $mtable ms
                  WHERE h.observer = ? 
                  AND mo.regid = h.observer
                  AND ms.regid = h.subject";
        $bindvars = [$observer_id];
        if (!empty($subject_id)) {
            $query .= " AND h.subject = ?";
            $bindvars[] = $subject_id;
        }
        if (!empty($scope)) {
            $scope = strtolower($scope);
            $query .= " AND h.scope = ?";
            $bindvars[] = $scope;
        }

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return [];
        }
        $subjects = [];
        while ($result->next()) {
            [$module, $itemtype, $scope] = $result->fields;
            $subjects[$module][$itemtype][$scope] = 1;
        }
        return $subjects;
    }

    /**
     * Get a list of hook modules (observers) attached (hooked)
     * to a specific module (subject) (+itemtype) event
     * @return array<string, mixed>
    **/
    public function getSubjectObservers($subject, $event, $itemtype = null): array
    {
        $msg = 'Invalid #(1) for xarHooks::getSubjectObservers()';
        if (empty($subject) || !is_string($subject)) {
            throw new BadParameterException('subject', $msg);
        }
        if (empty($event) || !is_string($event)) {
            throw new BadParameterException('event', $msg);
        }
        if (isset($itemtype) && !is_numeric($itemtype)) {
            throw new BadParameterException('itemtype', $msg);
        }
        $xar = $this->getServicesClass();

        $subject_id = $xar->mod()->getRegID($subject);
        if (empty($subject_id)) {
            return [];
        }

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $query = "SELECT mo.name, eo.event, eo.scope
                  FROM $htable h, $mtable mo, $etable eo
                  WHERE h.subject = ?
                  AND mo.regid = h.observer
                  AND eo.module_id = h.observer
                  AND eo.event = ?";
        $bindvars = [$subject_id, $event];
        if (!empty($itemtype)) {
            $query .= " AND ( h.itemtype = ? OR h.itemtype = ? )";
            $bindvars[] = $itemtype;
            $bindvars[] = 0;
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return [];
        }

        $observers = [];
        while ($result->next()) {
            [$module, $event, $scope] = $result->fields;
            $observers[] = [
                'module' => $module,
                'event' => $event,
                'scope' => $scope,
            ];
        }
        return $observers;
    }
}
