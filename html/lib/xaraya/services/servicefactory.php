<?php

/**
 * Service Factory for core service classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use xarCore;
use xarLog;

/**
 * Service Factory for core service classes
 */
class ServiceFactory
{
    public const SLICE = 'factory';

    /**
     * This distinguishes between shared services and parent-aware services, where shared services
     * are common for all within a single request and are provided from StaticServicesClass, while
     * parent-aware services are cloned for each parent so that methods like getModName() will work.
     * @todo Some services are actually independent of request, but should use connection pool (db)
     * or central processor (log) to safely work in fiber or coroutine environments (besides using
     * async drivers)
     */
    /** @var list<string> */
    public static array $sharedServices = [
        // shared services
        'ctl', 'log', 'mls', 'tpl', 'var', 'block', 'data', 'prop', 'cache', 'mem', 'req', 'config', 'session', 'db',
        // parent-aware services = not shared
        // 'sec',
        // static wrappers
        'prep', 'events', 'hooked', 'theme',
        // internal helpers
        'modules.vars', 'modules.user', 'modules.item', 'modules.info', 'modules.exec', 'modules.hooks', 'modules.alias',
        // created directly = not via ServiceFactory
        // 'caching.output', 'events.config', ...
    ];
    /** @var list<string> */
    public static array $argumentServices = [
        // shared services with optional argument
        'user', 'system',
        // parent-aware services with optional argument = not shared
        // 'mod',
    ];

    /**
     * Create a new service instance (prototype) for the given name.
     * This method is responsible for knowing how to construct each service type.
     *
     * @param string $name The name of the service to create.
     * @param ServicesInterface $parent The parent object (typically StaticServicesClass) for the service.
     * @return ServiceInterface The newly created service instance.
     * @throws \Exception If the service name is unsupported.
     */
    public static function createServicePrototype(string $name, ServicesInterface $parent): ServiceInterface
    {
        return match ($name) {
            // public services
            'ctl' => self::getControllerService($parent),
            'log' => self::getLoggerService($parent),
            'mls' => self::getMultiLanguageService($parent),
            'mod' => self::getModulesService($parent),
            'sec' => self::getSecurityService($parent),
            'tpl' => self::getTemplatingService($parent),
            'var' => self::getVariablesService($parent),
            'block' => self::getBlocksService($parent),
            'data' => self::getDataObjectService($parent),
            'prop' => self::getDataPropertyService($parent),
            'cache' => self::getCachingService($parent),
            'config' => self::getConfigService($parent),
            'system' => self::getSystemService($parent),
            'req' => self::getRequestService($parent),
            'mem' => self::getMemoryService($parent),
            'session' => self::getSessionService($parent),
            'user' => self::getUserService($parent),
            'db' => self::getDatabaseService($parent),
            // wrappers for static core classes
            'prep' => self::getVarPrepService($parent),
            'events' => self::getEventsService($parent),
            'hooked' => self::getHookedService($parent),
            'theme' => self::getThemesService($parent),
            // internal modules helpers
            'modules.vars' => self::getModuleVarsHelper($parent),
            'modules.user' => self::getModuleUserVarsHelper($parent),
            'modules.item' => self::getModuleItemVarsHelper($parent),
            'modules.info' => self::getModuleInfoHelper($parent),
            'modules.exec' => self::getModuleExecHelper($parent),
            'modules.hooks' => self::getModuleHooksHelper($parent),
            'modules.alias' => self::getModuleAliasHelper($parent),
            default => throw new \Exception('Unsupported service ' . $name),
        };
    }

    /**
     * Summary of getControllerService
     */
    public static function getControllerService(ServicesInterface $parent): ControllerInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - install.php will pass along $config
        return ControllerService::create($parent);
    }

    /**
     * Summary of getLoggerService
     */
    public static function getLoggerService(object|string|null $parent = null): LoggerInterface
    {
        self::log(__METHOD__, $parent);
        $logger = LoggerService::create($parent);
        // initialize service
        $logger->init();
        return $logger;
    }

    /**
     * Summary of getMultiLanguageService
     */
    public static function getMultiLanguageService(object|string|null $parent): MultiLanguageInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - install.php will pass along $config
        return MultiLanguageService::create($parent);
    }

    /**
     * Summary of getModulesService
     */
    public static function getModulesService(object|string|null $parent): ModulesInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - checked with xar::mod()->isLoaded()
        return ModulesService::create($parent);
    }

    /**
     * Summary of getSecurityService
     */
    public static function getSecurityService(ServicesInterface $parent): SecurityInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return SecurityService::create($parent);
    }

    /**
     * Summary of getTemplatingService
     */
    public static function getTemplatingService(ServicesInterface $parent): TemplatingInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - install.php will pass along $config + needed to initialize the template cache
        return TemplatingService::create($parent);
    }

    /**
     * Summary of getVariablesService
     */
    public static function getVariablesService(object|string|null $parent): VariablesInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - @todo clean up config_vars db table and xarVarPrep::init()
        return VariablesService::create($parent);
    }

    /**
     * Summary of getBlocksService
     */
    public static function getBlocksService(object|string|null $parent): BlocksInterface
    {
        self::log(__METHOD__, $parent);
        $blocks = BlocksService::create($parent);
        // initialize service
        $blocks->init();
        return $blocks;
    }

    /**
     * Summary of getDataObjectService
     */
    public static function getDataObjectService(object|string|null $parent): DataObjectInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return DataObjectService::create($parent);
    }

    /**
     * Summary of getDataPropertyService
     */
    public static function getDataPropertyService(ServicesInterface $parent): DataPropertyInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return DataPropertyService::create($parent);
    }

    /**
     * Summary of getCachingService
     */
    public static function getCachingService(object|string|null $parent = null): CachingInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - delay until we need results
        return CachingService::create($parent);
    }

    /**
     * Summary of getConfigService
     */
    public static function getConfigService(object|string|null $parent = null): ConfigInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here - @todo clean up config_vars db table with VariablesService
        return ConfigService::create($parent);
    }

    /**
     * Summary of getSystemService
     */
    public static function getSystemService(object|string|null $parent = null): SystemInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return SystemService::create($parent);
    }

    public static function getMemoryService(object|string|null $parent = null): MemoryInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - xar::cache()->init() will pass along $config
        return MemoryService::create($parent);
    }

    public static function getRequestService(object|string|null $parent = null): RequestInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - install.php will pass along $config
        return RequestService::create($parent);
    }

    /**
     * Summary of getSessionService
     * @todo integrate SessionHandler vs. SessionContext options
     */
    public static function getSessionService(object|string|null $parent = null): SessionInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - depends on the caller using xar::session()->start() or not
        return SessionService::create($parent);
    }

    /**
     * Summary of getUserService
     */
    public static function getUserService(object|string|null $parent = null): UserInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - checked with xar::user()->isLoaded()
        return UserService::create($parent);
    }

    /**
     * Summary of getExitCallable
     */
    public static function getExitCallable(ServicesInterface $parent): callable
    {
        self::log(__METHOD__, $parent);
        return function (int|string $status = 0) use ($parent) {
            xarLog::message('Exit from ' . $parent::class, xarLog::LEVEL_NOTICE);
            xarCore::exit($status);
        };
    }

    public static function getDatabaseService(object|string|null $parent = null): DatabaseInterface
    {
        self::log(__METHOD__, $parent);
        // do *not* initialize service here - this can be called several times (see installer phase5)
        return DatabaseService::create($parent);
    }

    public static function getVarPrepService(object|string|null $parent = null): VarPrepInterface
    {
        $className = \xarVarPrep::class;
        $instance = null;
        self::log(__METHOD__ . "($className)", $parent);
        $varprep = VarPrepService::create($parent, $className, $instance);
        // initialize wrapped class by default here
        $varprep->init();
        return $varprep;
    }

    public static function getEventsService(object|string|null $parent = null): EventsInterface
    {
        self::log(__METHOD__, $parent);
        $events = EventsService::create($parent);
        // initialize service
        $events->init();
        return $events;
    }

    public static function getHookedService(object|string|null $parent = null): HookedInterface
    {
        self::log(__METHOD__, $parent);
        $hooked = HookedService::create($parent);
        // initialize service
        $hooked->init();
        return $hooked;
    }

    public static function getThemesService(object|string|null $parent = null): ThemesInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return ThemesService::create($parent);
    }

    public static function getWrapperService(object|string|null $parent = null, $className = null, $instance = null): ServiceInterface
    {
        self::log(__METHOD__ . "($className)", $parent);
        $wrapper = WrapperService::create($parent, $className, $instance);
        // initialize wrapped class by default here
        $wrapper->init();
        return $wrapper;
    }

    public static function getModuleVarsHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\VarsHelper($parent);
    }

    public static function getModuleUserVarsHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\UserVarsHelper($parent);
    }

    public static function getModuleItemVarsHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\ItemVarsHelper($parent);
    }

    public static function getModuleInfoHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\InfoHelper($parent);
    }

    public static function getModuleExecHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\ExecHelper($parent);
    }

    public static function getModuleHooksHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\HooksHelper($parent);
    }

    public static function getModuleAliasHelper(ServicesInterface $parent): ServiceInterface
    {
        self::log(__METHOD__, $parent);
        // no init() here
        return new Modules\AliasHelper($parent);
    }

    protected static function log(string $method, object|string|null $parent = null): void
    {
        $level = xarLog::LEVEL_DEBUG;
        $level = xarLog::LEVEL_INFO;
        if (!isset($parent)) {
            xarLog::message($method . ': starting service for <unknown>', $level);
        } elseif (is_string($parent)) {
            xarLog::message($method . ': starting service for ' . $parent, $level);
        } elseif (is_object($parent)) {
            xarLog::message($method . ': starting service for ' . $parent::class . ' ' . spl_object_id($parent), $level);
        } else {
            // no idea what we got here - let's find out
            xarLog::message($method . ': starting service for ' . var_export($parent, true), $level);
        }
    }
}
