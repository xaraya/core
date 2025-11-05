<?php

namespace Xaraya\Modules;

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Requests\RequestHandler;
use Xaraya\Context\SessionContext;
use Xaraya\Sessions\SessionHandler;
use Xaraya\Services\TestHelper as ServicesHelper;
use xarCache;
use xarDatabase;
use xarEvents;
use xarLog;
use sys;
use LogicException;

/**
 * TestHelper for unit testing module class & method class
 */
class TestHelper extends TestCase
{
    protected static string $oldDir;

    public static function setUpBeforeClass(): void
    {
        // initialize bootstrap
        sys::init();
        // initialize caching - delay until we need results
        xarCache::init();
        // initialize loggers
        xarLog::init();

        // create dummy context
        $context = static::createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        $xar = \Xaraya\Services\xar::setServicesContext($context);
        // use RequestContext as request handler
        $xar->req()->setRequestClass(RequestContext::class);
        // use SessionContext as session handler
        $xar->session()->setSessionClass(SessionContext::class);

        // initialize database - delay until caching fails
        xarDatabase::init();
        // initialize events
        xarEvents::init();
        // initialize modules
        $xar->mod()->init();
        // initialize server
        $xar->req()->init([]);
        // initialize session
        $xar->session()->init([]);
        // initialize users
        $xar->user()->init();

        // file paths are relative to html directory here
        static::$oldDir = (string) getcwd();
        chdir(dirname(__DIR__, 3));
    }

    public static function tearDownAfterClass(): void
    {
        $xar = \Xaraya\Services\xar::getServicesClass();
        // reset redirectTo callback in xarController
        $xar->ctl()->setCallback('redirectTo', null);
        // use default request handler
        $xar->req()->setRequestClass(RequestHandler::class);
        // use default session handler
        $xar->session()->setSessionClass(SessionHandler::class);

        chdir(static::$oldDir);
    }

    protected function setUp(): void {}

    protected function tearDown(): void {}

    /**
     * Create context with optional arguments
     * @param array<mixed> $args
     * @return Context<string, mixed>
     */
    protected static function createContext(array $args = [])
    {
        if (empty($args)) {
            $args = ['source' => __METHOD__];
        }
        return new Context($args);
    }

    /**
     * Create parent module for a module class
     * @param string $modName
     * @param class-string<ModuleServicesInterface> $className
     * @return ModuleInterface
     */
    protected function createModule(string $modName, string $className)
    {
        $xar = \Xaraya\Services\xar::getServicesClass();
        // Xaraya\Modules\MyFancyModule\UserApi
        $parts = explode('\\', $className);
        array_pop($parts);
        // Xaraya\Modules\MyFancyModule\Module
        $moduleName = implode('\\', $parts) . '\Module';
        assert(is_subclass_of($moduleName, ModuleInterface::class));
        //return new $moduleName($modName);
        return $xar->mod()->getModule($modName);
    }

    /**
     * Create parent component for a method class
     * @param string $modName
     * @param class-string<MethodServicesInterface<ModuleServicesInterface>> $className
     * @return ModuleServicesInterface
     */
    protected function createComponent(string $modName, string $className)
    {
        $xar = \Xaraya\Services\xar::getServicesClass();
        // Xaraya\Modules\MyFancyModule\UserApi\ViewMethod
        $parts = explode('\\', $className);
        array_pop($parts);
        // Xaraya\Modules\MyFancyModule\UserApi
        $parentName = implode('\\', $parts);
        assert(is_subclass_of($parentName, ModuleServicesInterface::class));
        //return new $parentName($modName);
        $classType = array_pop($parts);
        return $xar->mod()->getModule($modName)->getComponent($classType);
    }

    /**
     * Get parent class or module class
     * @param string $modName
     * @param class-string<ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>> $className
     * @return array<mixed>
     */
    protected function getConstructorArgs(string $modName, string $className)
    {
        if (is_subclass_of($className, MethodServicesInterface::class)) {
            $itemtype = 0;
            $parent = $this->createComponent($modName, $className);
            return [$modName, $itemtype, $parent];
        }
        if (is_subclass_of($className, ModuleServicesInterface::class)) {
            $parent = $this->createModule($modName, $className);
            return [$modName, $parent];
        }
        return [];
    }

    /**
     * Override checkAccess() method to return true + check if called $count times
     * @param string $modName
     * @param class-string<ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>> $className
     * @param int $count
     * @return ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>
     */
    protected function createMockWithAccess(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core security service class with mock too
        $helper = new ServicesHelper();
        $helper->createMockSecurityWithAccess($mock, $count);
        return $mock;
    }

    /**
     * Override callSecurityCheck() method to intercept redirect + check if called $count times
     * @param string $modName
     * @param class-string<ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>> $className
     * @param int $count
     * @return ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>
     */
    protected function createMockWithoutAccess(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core security service class with mock too
        $helper = new ServicesHelper();
        $helper->createMockSecurityWithoutAccess($mock, $count);
        return $mock;
    }

    /**
     * Override redirect() method to throw exception + check if called $count times
     * @param string $modName
     * @param class-string<ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>> $className
     * @param int $count
     * @return ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>
     */
    protected function createMockWithoutRedirect(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core controller service class with mock too
        $helper = new ServicesHelper();
        $helper->createMockControllerWithoutRedirect($mock, $count);
        return $mock;
    }

    /**
     * Override exit() method to throw exception + check if called $count times
     * @param string $modName
     * @param class-string<ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>> $className
     * @param int $count
     * @return ModuleServicesInterface|MethodServicesInterface<ModuleServicesInterface>
     */
    protected function createMockWithoutExit(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = $this->getMockBuilder($className)
            ->setConstructorArgs($args)
            ->onlyMethods(['exit'])
            ->getMock();
        // override exit() method to throw exception + check if called $count times
        $constraint = $this->atMost($count);
        $mock->expects($constraint)
            ->method('exit')
            ->willReturnCallback(function ($status = 0) {
                throw new LogicException("Called exit('$status')");
            });
        // override core exit service class with callable too
        $helper = new ServicesHelper();
        $helper->createMockServicesWithoutExit($mock, $count);
        return $mock;
    }
}
