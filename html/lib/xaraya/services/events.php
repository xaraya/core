<?php

/**
 * Events available via methods (WIP)
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

use Xaraya\Context\Context;
use ixarEventSubject;
use ixarMod;
use xarClassMap;
use sys;
use Exception;
use BadParameterException;
use ClassNotFoundException;
use FunctionNotFoundException;

/**
 * For documentation purposes only - available via EventsTrait
 */
interface EventsInterface extends ServiceInterface
{
    public function getSubjectType(): int;
    public function getObserverType(): int;
    public function notify($event, $args = [], $context = null): mixed;
    public function getSubject($event): array;
    public function getSubjects(): array;
    public function getObservers(ixarEventSubject $subject): array;
    public function fileLoad($info): bool;
    public function registerCallback($event, $callback): mixed;
    // EventsConfig methods
    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify'): mixed;
    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify'): mixed;
    public function unregisterSubject($event, $module): bool;
    public function unregisterObserver($event, $module): bool;
    public function getObserverModules(): array;
}

/**
 * Events available via methods
 */
trait EventsTrait
{
    use ServiceTrait;
}

/**
 * Access xarEvents::* methods (notify, ...)
 *
 * Available methods:
 * - notify()
 * - ...
 */
class EventsService implements EventsInterface
{
    use EventsTrait;

    public const SLICE = 'events';
    // Event system itemtypes
    public const SUBJECT_TYPE       = 1;   // System event subjects, handles OBSERVER_TYPE events
    public const OBSERVER_TYPE      = 2;   // System event observers
    public const SUPPORTED_AREAS    = ['class', 'api', 'gui'];

    // keep track of classname as detected in fileLoad() for register() - @todo handle service fileLoad() + static register()
    protected static $classnames = [];
    // for non-core modules we're only interested in hookobservers for now - this may extend to eventobservers later...
    protected static $classtypes = ['hookobservers'];
    // allow others to define callback functions without registering observers e.g. for event bridge
    protected $callbackFunctions = [];
    protected bool $initialized = false;
    private ?Config\EventsConfig $configService = null;

    private function getConfigService(): Config\EventsConfig
    {
        $this->configService ??= new Config\EventsConfig($this->getServicesClass());
        return $this->configService;
    }

    public function getSubjectType(): int
    {
        return self::SUBJECT_TYPE;
    }

    public function getObserverType(): int
    {
        return self::OBSERVER_TYPE;
    }

    public function init(array $args = []): bool
    {
        if (empty($args) && $this->initialized) {
            return true;
        }
        $xar = $this->getServicesClass();
        // Register tables this subsystem uses
        $tables = ['eventsystem' => $xar->db()->getPrefix() . '_eventsystem'];
        $xar->db()->importTables($tables);
        $this->initialized = true;
        return true;
    }

    /**
     * public event notifier function
     *
     * @param string $event name of event subject, required
     * @param mixed $args argument(s) to pass to subject, optional, default empty array
     * @param ?Context<string, mixed> $context
     * @return mixed response from subject notify method
    **/
    public function notify($event, $args = [], $context = null): mixed
    {
        $xar = $this->getServicesClass();
        $info = [];
        // Attempt to load subject
        try {
            // get info for specified event
            $info = $this->getSubject($event);
            if (empty($info)) {
                return null;
            }
            // context for core services is set in handler
            if (!isset($context)) {
                // $context = new Context(['source' => __METHOD__]);
                // Use context from static services class here
                $context = $xar->getContext();
            }
            // file load takes care of validation for us
            if (!$this->fileLoad($info)) {
                return null;
            }
            $module = $info['module'];
            switch (strtolower($info['area'])) {
                // support namespaces in modules (and core someday) - we may use $info['classname'] here
                case 'class':
                    // define class (loadFile already checked it exists)
                    $classname = $info['classname'] ?: ucfirst($module) . $info['event'] . "Subject";
                    // create subject instance, passing $args from caller + $xar services class
                    $subject = new $classname($args, $xar);
                    // set context if available in notify call
                    $subject->setContext($context);
                    // get observer info from subject
                    $obsinfo = $this->getObservers($subject);
                    if (!empty($obsinfo)) {
                        foreach ($obsinfo as $obs) {
                            // Attempt to load observer
                            try {
                                if (!$this->fileLoad($obs)) {
                                    continue;
                                }
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
                                        $obsclass = "ApiEventObserver";
                                        $subject->attach(new $obsclass($obs));
                                        break;
                                    case 'gui':
                                        // wrap gui function in guiclass observer
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
                    // context for core services is set in handler
                    $response = $xar->mod()->apiFunc($module, $info['type'], $info['func'], $args);
                    break;
                case 'gui':
                    // not allowed in event subjects
                default:
                    $response = false;
                    break;
            }
        } catch (Exception $e) {
            // Events never fail, ever!
            $xar->log()->critical("xarEvents::notify: failed notifying $event subject observers");
            $xar->log()->info("xarEvents::notify: Reason: " . $e->getMessage());
            $response = false;
        }

        $info['event'] ??= $event;
        $info['caller'] = static::class;
        $info['args'] = $args;
        // allow others to define callback functions without registering observers e.g. for event bridge (= not saved in database)
        if (!empty($this->callbackFunctions) && !empty($this->callbackFunctions[$event])) {
            foreach ($this->callbackFunctions[$event] as $callback) {
                try {
                    call_user_func($callback, $info, $context);
                } catch (Exception $e) {
                    $xar->log()->info("xarEvents::notify: callback $event error " . $e->getMessage());
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
                    $xar->log()->info("xarEvents::notify: callback $event error " . $e->getMessage());
                }
            }
        }

        // now notify Event subject observers that an event was just raised
        // (these are generic listeners that observe every event raised)
        // We only do this if this isn't the generic Event itself...
        if ($event != 'Event') {
            $xar->events()->notify('Event', $info, $context);
        }

        // return the response
        return $response;

    }

    /**
     * get functions
    **/
    /**
     * Get db info for an event subject
     *
     * @param string $event name of event subject, required
     * @return array<string, mixed> array of subject info or empty array
     * used internally by the event system, must not be overloaded
    **/
    final public function getSubject($event): array
    {
        // init the cache, if it isn't already init'ed
        $subjects = $this->getSubjects();
        if (!isset($subjects[$event])) {
            $subjects[$event] = [];
        }
        return $subjects[$event];
    }

    /**
     * Load all subjects from db for current subject type
     * We only ever do this once per page request, results are cached
     * @return array<string, mixed> containing subjects, indexed by event name
     * used internally by the event system, must not be overloaded
    **/
    final public function getSubjects(): array
    {
        // Note: this is overridden in HookedService()
        $subjecttype = $this->getSubjectType();
        $xar = $this->getServicesClass();
        // Cached event subjects and observers
        $cacheScope = 'Events.Subjects';
        $cacheName = $subjecttype;
        if ($xar->mem()->has($cacheScope, $cacheName)) {
            $subjects = $xar->mem()->get($cacheScope, $cacheName);
            assert(is_array($subjects));
            return $subjects;
        }

        // initialize the cache (we only ever run this query once per subject type)
        $subjects = [];

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['modules'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $etable = $xartable['eventsystem'];
        $mtable = $xartable['modules'];
        $bindvars = [];
        $where = [];
        // support namespaces in modules (and core someday) - we may get back $classname here
        $query = "SELECT es.id, es.event, es.module_id, es.area, es.type, es.func, es.itemtype, es.class, es.scope,
                         ms.name
                  FROM $etable es, $mtable ms";
        // get subjects for valid, active modules only
        $where[] = "es.module_id = ms.regid";
        $where[] = "ms.state = ?";
        $bindvars[] = ixarMod::STATE_ACTIVE;
        // get subjects for current subjecttype
        $where[] = "es.itemtype = ?";
        $bindvars[] = $subjecttype;
        $query .= " WHERE " . join(" AND ", $where);
        // order by module, event (doesn't really matter for subjects)
        $query .= " ORDER BY ms.name ASC, es.event ASC";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return [];
        }
        while ($result->next()) {
            [$id, $event, $module_id, $area, $type, $func, $itemtype, $classname, $scope, $module] = $result->fields;
            // cache results
            $subjects[$event] = [
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
            ];
        };
        $result->close();
        // return cached results
        $xar->mem()->set($cacheScope, $cacheName, $subjects);
        return $subjects;
    }

    /**
     * Get all observers of an event subject from db
     * Note: this is overridden in HookedService()
     *
     * @param ixarEventSubject $subject ixarEventSubject
     * @return array<string, mixed> containing subject observers
    **/
    public function getObservers(ixarEventSubject $subject): array
    {
        $xar = $this->getServicesClass();
        $event = $subject->getSubject();
        $info = $this->getSubject($event);
        // Note: this is overridden in HookedService()
        $subjecttype = $this->getSubjectType();
        if (empty($info) || $info['itemtype'] != $subjecttype) {
            return [];
        }
        // Note: this is overridden in HookedService()
        $observertype = $this->getObserverType();
        // Cached event subjects and observers
        $cacheScope = 'Events.Observers';
        $cacheName = $observertype;
        $observers = [];
        if ($xar->mem()->has($cacheScope, $cacheName)) {
            $observers = $xar->mem()->get($cacheScope, $cacheName);
            if (isset($observers[$event])) {
                return $observers[$event];
            }
        }

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
        // get all registered observers to registered subjects
        $query = "SELECT o.id, o.event, o.module_id, mo.name, o.area, o.type, o.func, o.itemtype, o.class
                  FROM $etable o, $etable s, $mtable mo, $mtable ms";

        // make sure we only get observers to registered subjects :)
        $where[] =  "o.event = s.event";

        // only get subjects belonging to a registered module
        $where[] = "ms.regid = s.module_id";
        // only get subjects of active modules
        $where[] = "ms.state = ?";
        $bindvars[] = ixarMod::STATE_ACTIVE;
        // only get subjects for the current subject itemtype
        $where[] =  "s.itemtype = ?";
        $bindvars[] = $subjecttype;
        // only get observers belonging to a registered module
        $where[] = "mo.regid = o.module_id";
        // only get observers of active modules
        $where[] = "mo.state = ?";
        $bindvars[] = ixarMod::STATE_ACTIVE;
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
        if (!$result) {
            return [];
        }
        while ($result->next()) {
            [$id, $evt, $module_id, $module, $area, $type, $func, $itemtype, $classname] = $result->fields;
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
        if (!isset($observers[$event])) {
            $observers[$event] = [];
        }

        $xar->mem()->set($cacheScope, $cacheName, $observers);
        return $observers[$event];
    }

    public function fileLoad($info): bool
    {
        extract($info);

        // validate input, some methods (register/notify) use this to validate input
        $invalid = [];
        // Check we have a valid event
        /** @var string $event */
        if (empty($event) || !is_string($event) || strlen($event) > 255) {
            $invalid[] = 'event';
        }
        $xar = $this->getServicesClass();

        // Check we have a valid module
        /** @var string $module */
        if (empty($module) || is_numeric($module) || empty($module_id) || !is_numeric($module_id)) {
            if (!empty($module)) {
                $module_id = is_numeric($module) ? $module : $xar->mod()->getRegID($module);
            }
            /** @var int $module_id */
            if (!empty($module_id)) {
                $modinfo = $xar->mod()->getInfo($module_id);
            }
            // can't check mod available here, since it may not be if the module is init'ing
            /** @var array<mixed> $modinfo */
            //if (empty($modinfo) || !$xar->mod()->isAvailable($modinfo['name']))
            if (!empty($modinfo)) {
                $module = $modinfo['name'];
            } else {
                $invalid[] = 'module';
            }
        }

        // Check we have a valid area (class, api, gui)
        /** @var string $area */
        if (empty($area) || !is_string($area) || strlen($area) > 64) {
            $invalid[] = 'area';
        }

        // Check we have a valid type (eventobserver, eventsubject, admin, user, event, etc)
        /** @var string $type */
        if (empty($type) || !is_string($type) || strlen($type) > 64) {
            $invalid[] = 'type';
        }

        // Check we have a valid func
        /** @var string $func */
        if (empty($func) || !is_string($func) || strlen($func) > 64) {
            $invalid[] = 'func';
        }

        /** @var int $itemtype */
        if (empty($itemtype) || !is_numeric($itemtype)) {
            // not a valid subject or observer itemtype
            $invalid[] = 'itemtype';
        }

        if (!empty($invalid)) {
            $vars = [join(', ', $invalid), 'register', 'xarEvent'];
            $msg = "Invalid #(1) for method #(2)() in class #(3)";
            throw new BadParameterException($vars, $msg);
        }

        $area = strtolower($area);
        static $_files = [];
        if (isset($_files[$itemtype][$event][$module])) {
            return $_files[$itemtype][$event][$module];
        }
        $loaded = false;

        $suffix = '';
        switch ($area) {
            case 'class':
            default:
                // Note: this is overridden in HookedService()
                if ($itemtype == $this->getSubjectType()) {
                    $suffix = 'Subject';
                } elseif ($itemtype == $this->getObserverType()) {
                    $suffix = 'Observer';
                }
                if (empty($func)) {
                    $func = 'notify';
                }
                $filename = strtolower($event);
                // support namespaces in modules (and core someday) - we may detect or use $info['classname'] here
                if (empty($info['classname'])) {
                    // for non-core modules we're only interested in hookobservers for now - this may extend to eventobservers later...
                    if (in_array($info['type'], static::$classtypes)) {
                        $result = xarClassMap::findHookObserver($module, $event);
                        if (!empty($result)) {
                            $classname = $result['classname'];
                            // import the file (raises exception if file not found)
                            require_once($result['filepath']);
                        } else {
                            // we try to get the actual $classname here first
                            $oldclasses = get_declared_classes();
                            // import the file (raises exception if file not found)
                            sys::import("modules.{$module}.class.{$type}.{$filename}");
                            $newclasses = get_declared_classes();
                            // assuming new classes in namespaces only have 1 class definition per file as they should...
                            $diffclasses = array_values(array_diff($newclasses, $oldclasses, ['HookObserver', 'EventObserver', 'HookSubject', 'EventSubject']));
                            $xar->log()->info("xarEvents::fileLoad: found classes " . implode(', ', $diffclasses));
                            if (count($diffclasses) > 0) {
                                $classname = $diffclasses[0];
                            } else {
                                $classname = ucfirst($module) . $event . $suffix;
                            }
                        }
                        // keep track of classname as detected in fileLoad() for register()
                        $classkey = implode(':', [$info['event'], $info['module'], $info['type']]);
                        static::$classnames[$classkey] = $classname;
                    } else {
                        $result = xarClassMap::findClassFile($info['type'], $module, $event);
                        if (!empty($result)) {
                            $classname = $result['classname'];
                            // import the file (raises exception if file not found)
                            require_once($result['filepath']);
                        } else {
                            // import the file (raises exception if file not found)
                            sys::import("modules.{$module}.class.{$type}.{$filename}");
                            $classname = ucfirst($module) . $event . $suffix;
                        }
                    }
                } else {
                    // import the file (raises exception if file not found)
                    sys::import("modules.{$module}.class.{$type}.{$filename}");
                    $classname = $info['classname'];
                }
                if (!class_exists($classname)) {
                    throw new ClassNotFoundException($classname);
                }
                if (!method_exists($classname, $func)) {
                    throw new FunctionNotFoundException($func);
                }
                // one class file loaded :)
                $loaded = true;
                break;

            case 'gui':
            case 'api':
                // use name of event as function name if none specified
                if (empty($func)) {
                    $func = strtolower($event);
                }
                // use name of func as filename
                $filename = strtolower($func);

                // see xar::mod()->callFunc() - pass modType . funcType as modType here for module classes
                $modType = $type . $area;
                // old-style module_type_func() hook function called via module class
                $callable = $xar->mod()->getModuleClassMethod($module, $modType, $filename, 'api');
                if (!empty($callable)) {
                    // one function file loaded :)
                    $loaded = true;
                    break;
                }
                // let's fall through until we find the function (or not)

                // determine the type folder to look in (xartype|xartypeapi)
                $type = $area == 'gui' ? $type : $type . $area;
                // define the function name (module_xartype(api)_func);
                $func = $module . '_' . $type . '_' . $filename;
                // @checkme by importing the function directly here, we never call xar::mod()->apiLoad($module, $type)
                // or xar::mod()->load($module, $type) in xar::mod()->callFunc() later when calling the function in observer
                // import the file (raises exception if file not found)
                try {
                    // try for specific file in type folder (eg /module/xaruserapi/eventfunc.php)
                    // we want to catch any exception here so we can fall back
                    sys::import("modules.{$module}.xar{$type}.{$filename}");
                } catch (Exception $e) {
                    // fall back to generic type file (eg /module/xaruserapi.php)
                    // we don't catch any exception here
                    try {
                        sys::import("modules.{$module}.xar{$type}");
                    } catch (Exception $e) {
                        throw new FunctionNotFoundException($func);
                    }
                }
                // check function exists
                if (!function_exists($func)) {
                    throw new FunctionNotFoundException($func);
                }
                // one function file loaded :)
                $loaded = true;
                break;
        }
        return $_files[$itemtype][$event][$module] = $loaded;
    }

    /**
     * allow others to define callback functions without registering observers e.g. for event bridge (= not saved in database)
     */
    public function registerCallback($event, $callback): mixed
    {
        $this->callbackFunctions[$event] ??= [];
        $this->callbackFunctions[$event][] = $callback;
        return true;
    }

    public function registerSubject($event, $scope, $module, $classnameOrArea = 'class', $type = 'eventsubjects', $func = 'notify'): mixed
    {
        return $this->getConfigService()->registerSubject($event, $scope, $module, $classnameOrArea, $type, $func);
    }

    public function registerObserver($event, $module, $classnameOrArea = 'class', $type = 'eventobservers', $func = 'notify'): mixed
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

    public function getObserverModules(): array
    {
        return $this->getConfigService()->getObserverModules();
    }
}
