<?php

/**
 * Service Factory for core service classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
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
    /** @var array<string> */
    public static array $sharedServices = ['ctl', 'log', 'mls', 'var', 'cache', 'config', 'session', 'db'];

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
            'session' => self::getSessionService($parent),
            'user' => self::getUserService($parent),
            'db' => self::getDatabaseService($parent),
            default => throw new \Exception('Unsupported service ' . $name),
        };
    }

    /**
     * Summary of getControllerService
     */
    public static function getControllerService(ServicesInterface $parent): ControllerInterface
    {
        self::log(__METHOD__, $parent);
        return ControllerService::create($parent);
    }

    /**
     * Summary of getLoggerService
     */
    public static function getLoggerService(object|string|null $parent = null): LoggerInterface
    {
        self::log(__METHOD__, $parent);
        return LoggerService::create($parent);
    }

    /**
     * Summary of getMultiLanguageService
     */
    public static function getMultiLanguageService(object|string|null $parent): MultiLanguageInterface
    {
        self::log(__METHOD__, $parent);
        return MultiLanguageService::create($parent);
    }

    /**
     * Summary of getModulesService
     */
    public static function getModulesService(object|string|null $parent): ModulesInterface
    {
        self::log(__METHOD__, $parent);
        return ModulesService::create($parent);
    }

    /**
     * Summary of getSecurityService
     */
    public static function getSecurityService(ServicesInterface $parent): SecurityInterface
    {
        self::log(__METHOD__, $parent);
        return SecurityService::create($parent);
    }

    /**
     * Summary of getTemplatingService
     */
    public static function getTemplatingService(ServicesInterface $parent): TemplatingInterface
    {
        self::log(__METHOD__, $parent);
        return TemplatingService::create($parent);
    }

    /**
     * Summary of getVariablesService
     */
    public static function getVariablesService(object|string|null $parent): VariablesInterface
    {
        self::log(__METHOD__, $parent);
        return VariablesService::create($parent);
    }

    /**
     * Summary of getBlocksService
     */
    public static function getBlocksService(object|string|null $parent): BlocksInterface
    {
        self::log(__METHOD__, $parent);
        return BlocksService::create($parent);
    }

    /**
     * Summary of getDataObjectService
     */
    public static function getDataObjectService(object|string|null $parent): DataObjectInterface
    {
        self::log(__METHOD__, $parent);
        return DataObjectService::create($parent);
    }

    /**
     * Summary of getDataPropertyService
     */
    public static function getDataPropertyService(ServicesInterface $parent): DataPropertyInterface
    {
        self::log(__METHOD__, $parent);
        return DataPropertyService::create($parent);
    }

    /**
     * Summary of getCachingService
     */
    public static function getCachingService(object|string|null $parent = null): CachingInterface
    {
        self::log(__METHOD__, $parent);
        return CachingService::create($parent);
    }

    /**
     * Summary of getConfigService
     */
    public static function getConfigService(object|string|null $parent = null): ConfigInterface
    {
        self::log(__METHOD__, $parent);
        return ConfigService::create($parent);
    }

    /**
     * Summary of getSessionService
     * @todo integrate SessionHandler vs. SessionContext options
     */
    public static function getSessionService(object|string|null $parent = null): SessionInterface
    {
        self::log(__METHOD__, $parent);
        return SessionService::create($parent);
    }

    /**
     * Summary of getUserService
     */
    public static function getUserService(object|string|null $parent = null): UserInterface
    {
        self::log(__METHOD__, $parent);
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
        return DatabaseService::create($parent);
    }

    protected static function log(string $method, object|string|null $parent = null): void
    {
        $level = xarLog::LEVEL_DEBUG;
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
