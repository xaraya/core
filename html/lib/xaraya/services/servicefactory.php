<?php

/**
 * Service Factory for core service classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
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
    /**
     * Summary of getControllerService
     */
    public static function getControllerService(ServicesInterface $parent): ControllerInterface
    {
        self::log(__METHOD__, $parent);
        return new ControllerService($parent);
    }

    /**
     * Summary of getLoggerService
     */
    public static function getLoggerService(ServicesInterface $parent): LoggerInterface
    {
        self::log(__METHOD__, $parent);
        return new LoggerService($parent);
    }

    /**
     * Summary of getMultiLanguageService
     */
    public static function getMultiLanguageService(ServicesInterface $parent): MultiLanguageInterface
    {
        self::log(__METHOD__, $parent);
        return new MultiLanguageService($parent);
    }

    /**
     * Summary of getModulesService
     */
    public static function getModulesService(ServicesInterface $parent): ModulesInterface
    {
        self::log(__METHOD__, $parent);
        return new ModulesService($parent);
    }

    /**
     * Summary of getSecurityService
     */
    public static function getSecurityService(ServicesInterface $parent): SecurityInterface
    {
        self::log(__METHOD__, $parent);
        return new SecurityService($parent);
    }

    /**
     * Summary of getTemplatingService
     */
    public static function getTemplatingService(ServicesInterface $parent): TemplatingInterface
    {
        self::log(__METHOD__, $parent);
        return new TemplatingService($parent);
    }

    /**
     * Summary of getVariablesService
     */
    public static function getVariablesService(ServicesInterface $parent): VariablesInterface
    {
        self::log(__METHOD__, $parent);
        return new VariablesService($parent);
    }

    /**
     * Summary of getBlocksService
     */
    public static function getBlocksService(ServicesInterface $parent): BlocksInterface
    {
        self::log(__METHOD__, $parent);
        return new BlocksService($parent);
    }

    /**
     * Summary of getDataObjectService
     */
    public static function getDataObjectService(ServicesInterface $parent): DataObjectInterface
    {
        self::log(__METHOD__, $parent);
        return new DataObjectService($parent);
    }

    /**
     * Summary of getDataPropertyService
     */
    public static function getDataPropertyService(ServicesInterface $parent): DataPropertyInterface
    {
        self::log(__METHOD__, $parent);
        return new DataPropertyService($parent);
    }

    /**
     * Summary of getCachingService
     */
    public static function getCachingService(ServicesInterface $parent): CachingInterface
    {
        self::log(__METHOD__, $parent);
        //return new CachingService($parent);
        return CachingService::create($parent);
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

    /**
     * Summary of getDatabaseService
     */
    public static function getDatabaseService(?object $parent = null): DatabaseInterface
    {
        self::log(__METHOD__, $parent);
        //return new DatabaseService($parent);
        return DatabaseService::create($parent);
    }

    protected static function log(string $method, ?object $parent = null): void
    {
        if (!isset($parent)) {
            xarLog::message($method . ': starting service for <unknown>', xarLog::LEVEL_DEBUG);
        } else {
            xarLog::message($method . ': starting service for ' . $parent::class, xarLog::LEVEL_DEBUG);
        }
    }
}
