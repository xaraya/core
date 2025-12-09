<?php

/**
 * Hooks available via methods (WIP)
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

namespace Xaraya\Services;

use ixarEventSubject;
use ixarHookSubject;
use ixarMod;

/**
 * For documentation purposes only - available via HookedTrait
 */
interface HookedInterface extends EventsInterface
{
    public function getConfigService(): Config\HookedConfig;
    public function getSubjectType(): int;
    public function getObserverType(): int;
    public function getObservers(ixarEventSubject $subject): array;
    // HookedConfig methods
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
 * Hooks available via methods
 */
trait HookedTrait
{
    use EventsTrait;
}

/**
 * Access xarHooks::* methods (notify, ...)
 *
 * Available methods:
 * - notify()
 * - isAttached()
 * - ...
 */
class HookedService extends EventsService implements HookedInterface
{
    use HookedTrait;

    public const SLICE = 'hooked';
    // unique event system itemtype ids for storage/retrieval/actioning in the event system
    public const HOOK_SUBJECT_TYPE  = 3;
    public const HOOK_OBSERVER_TYPE = 4;

    protected static $hookobservers = [];
    // allow others to define callback functions without registering observers e.g. for event bridge
    protected $callbackFunctions = [];
    private ?Config\HookedConfig $configService = null;

    public function getConfigService(): Config\HookedConfig
    {
        $this->configService ??= new Config\HookedConfig($this->getServicesClass());
        return $this->configService;
    }

    /**
     * required functions, provide event system with late static bindings for these values
    **/
    public function getSubjectType(): int
    {
        return self::HOOK_SUBJECT_TYPE;
    }

    public function getObserverType(): int
    {
        return self::HOOK_OBSERVER_TYPE;
    }

    // this function is called when an event is raised (hook called)
    // it returns all modules hooked to the caller module (+ itemtype) that raised the event
    // NOTE: This function is called by the event module, modify with caution
    /**
     * Summary of getObservers
     * @param ixarHookSubject $subject
     * @return array<string, mixed>
     */
    public function getObservers(ixarEventSubject $subject): array
    {
        $xar = $this->getServicesClass();
        $event = $subject->getSubject();
        $args = $subject->getExtrainfo();
        $info = $this->getSubject($event);
        if (empty($info)) {
            return [];
        }
        $subject_id = $args['module_id'];
        $subject_module = $args['module'];
        $subject_itemtype = empty($args['itemtype']) ? 0 : $args['itemtype'];

        $cacheScope = 'Hooks.Observers';
        $cacheName = $subject_module . '.' . $subject_itemtype;
        $observers = [];
        if ($xar->mem()->has($cacheScope, $cacheName)) {
            $observers = $xar->mem()->get($cacheScope, $cacheName);
            if (isset($observers[$event])) {
                return $observers[$event];
            }
        }
        // init cache
        $observers[$event] = [];

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
        $bindvars = [];
        $where = [];
        // support namespaces in modules (and core someday) - we may get back $classname here
        $query = "SELECT eo.id, eo.event, eo.module_id, eo.area, eo.type, eo.func, eo.itemtype, eo.class,
                         mo.name
                  FROM $htable h, $etable eo, $mtable mo, $etable es";
        // only get observers for the hooks observer itemtype
        $where[] =  "eo.itemtype = ?";
        $bindvars[] = self::HOOK_OBSERVER_TYPE;
        // only get observers of this event - we take all events at once now
        //$where[] = "eo.event = ?";
        //$bindvars[] = $event;
        // only from modules hooked to this subject
        $where[] = "h.subject = ?";
        $bindvars[] = $subject_id;
        $where[] = "eo.module_id = h.observer";
        // only get observers belonging to a registered module
        $where[] = "eo.module_id = mo.regid";
        // only get observers of active modules
        $where[] = "mo.state = ?";
        $bindvars[] = ixarMod::STATE_ACTIVE;

        // This excludes observers of one or more modules in order to avoid duplication
        // The common case is hooking DD to some itemtype that is already a dataobject:
        // We pass the itemid of the object through the hooks call, causing DD to display an object of the same itemid, which is of course the original object
        if (!empty($args['exclude_module'])) {
            //$query .= " AND mo.regid NOT IN ('" . join("','", $xar->mod()->getRegID($extraInfo['exclude_module'])) . "')";
            foreach ($args['exclude_module'] as $excluded_module) {
                $where[] = "mo.regid != " . $xar->mod()->getRegID($excluded_module);
            }
        }

        if (!empty($subject_itemtype)) {
            $where[] = "(h.itemtype = ? OR h.itemtype = ?)";
            $bindvars[] = $subject_itemtype;
            $bindvars[] = 0;
        } else {
            $where[] = "h.itemtype = ?";
            $bindvars[] = 0;
        }
        // only observers hooked for this scope
        $where[] = "eo.event = es.event";
        $where[] = "( h.scope = es.scope OR h.scope = ? )";
        $bindvars[] = '0';
        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event
        // @TODO: allow ordering ?
        $query .= " ORDER BY mo.name ASC, eo.event ASC";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return [];
        }
        while ($result->next()) {
            [$id, $evt, $module_id, $area, $type, $func, $itemtype, $classname, $module] = $result->fields;
            $observers[$evt] ??= [];
            $observers[$evt][$module] = [
                'id' => $id,
                'event' => $evt,
                'module_id' => $module_id,
                'module' => $module,
                'area' => $area,
                'type' => $type,
                'func' => $func,
                'itemtype' => $itemtype,
                'classname' => $classname,
            ];
        };
        $result->close();
        $xar->mem()->set($cacheScope, $cacheName, $observers);
        return $observers[$event];
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     * @return bool true if the observer is attached, false otherwise (for any reason)
    **/
    public function isAttached($observer, $subject, $itemtype = null, $scope = "0"): bool
    {
        return $this->getConfigService()->isAttached($observer, $subject, $itemtype, $scope);
    }

    public function attach($observer, $subject, $itemtype = null, $scope = "0"): bool
    {
        return $this->getConfigService()->attach($observer, $subject, $itemtype, $scope);
    }

    public function detach($observer, $subject, $itemtype = null, $scope = null): bool
    {
        return $this->getConfigService()->detach($observer, $subject, $itemtype, $scope);
    }

    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'hooksubjects', $func = 'notify'): mixed
    {
        return $this->getConfigService()->registerSubject($event, $scope, $module, $classnameOrArea, $type, $func);
    }

    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'hookobservers', $func = 'notify'): mixed
    {
        return $this->getConfigService()->registerObserver($event, $module, $classnameOrArea, $type, $func);
    }

    public function unregisterSubject($event, $module): bool
    {
        return $this->getConfigService()->unregisterSubject($event, $module);
    }

    public function unregisterObserver($event, $module): bool
    {
        return $this->getConfigService()->unregisterObserver($event, $module);
    }

    public function getObserverModules($observer = null): array
    {
        return $this->getConfigService()->getObserverModules($observer);
    }

    public function getObserverSubjects($observer, $subject = null, $scope = null): array
    {
        return $this->getConfigService()->getObserverSubjects($observer, $subject, $scope);
    }

    public function getSubjectObservers($subject, $event, $itemtype = null): array
    {
        return $this->getConfigService()->getSubjectObservers($subject, $event, $itemtype);
    }
}
