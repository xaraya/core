<?php

namespace Xaraya\Modules;

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Requests\RequestHandler;
use Xaraya\Context\SessionContext;
use Xaraya\Sessions\SessionHandler;
use Xaraya\Services\TestHelper as ServicesHelper;
use sys;
use LogicException;

/**
 * TestHelper for unit testing module class & method class
 */
class TestHelper extends TestCase
{
    protected static string $oldDir;
    protected static $xarServices;

    public static function setUpBeforeClass(): void
    {
        // initialize bootstrap
        sys::init();

        // create dummy context
        $context = static::createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        $xar = \Xaraya\Services\xar::setServicesContext($context);

        // initialize caching - delay until we need results
        $xar->cache()->init();
        // initialize loggers
        $xar->log()->init();

        // use RequestContext as request handler
        $xar->req()->setRequestClass(RequestContext::class);
        // use SessionContext as session handler
        $xar->session()->setSessionClass(SessionContext::class);

        // initialize database - delay until caching fails
        $xar->db()->init();
        // initialize events
        $xar->events()->init();
        // initialize modules
        $xar->mod()->init();
        // initialize server
        $xar->req()->init([]);
        // initialize session
        $xar->session()->init([]);
        // initialize users
        $xar->user()->init();

        static::$xarServices = $xar;

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

        if (isset(static::$oldDir)) {
            chdir(static::$oldDir);
        }
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
     * @param class-string<ModuleClassInterface> $className
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
     * @param class-string<MethodClassInterface<ModuleClassInterface>> $className
     * @return ModuleClassInterface
     */
    protected function createComponent(string $modName, string $className)
    {
        $xar = \Xaraya\Services\xar::getServicesClass();
        // Xaraya\Modules\MyFancyModule\UserApi\ViewMethod
        $parts = explode('\\', $className);
        array_pop($parts);
        // Xaraya\Modules\MyFancyModule\UserApi
        $parentName = implode('\\', $parts);
        assert(is_subclass_of($parentName, ModuleClassInterface::class));
        //return new $parentName($modName);
        $classType = array_pop($parts);
        return $xar->mod()->getModule($modName)->getComponent($classType);
    }

    /**
     * Get parent class or module class
     * @param string $modName
     * @param class-string<ModuleClassInterface|MethodClassInterface<ModuleClassInterface>> $className
     * @return array<mixed>
     */
    protected function getConstructorArgs(string $modName, string $className)
    {
        if (is_subclass_of($className, MethodClassInterface::class)) {
            $itemtype = 0;
            $parent = $this->createComponent($modName, $className);
            return [$modName, $itemtype, $parent];
        }
        if (is_subclass_of($className, ModuleClassInterface::class)) {
            $parent = $this->createModule($modName, $className);
            return [$modName, $parent];
        }
        return [];
    }

    /**
     * Override checkAccess() method to return true + check if called $count times
     * @param string $modName
     * @param class-string<ModuleClassInterface|MethodClassInterface<ModuleClassInterface>> $className
     * @param int $count
     * @return ModuleClassInterface|MethodClassInterface<ModuleClassInterface>
     */
    protected function createMockWithAccess(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core security service class with mock too
        $helper = new ServicesHelper('services');
        $helper->createMockSecurityWithAccess($mock, $count);
        return $mock;
    }

    /**
     * Override callSecurityCheck() method to intercept redirect + check if called $count times
     * @param string $modName
     * @param class-string<ModuleClassInterface|MethodClassInterface<ModuleClassInterface>> $className
     * @param int $count
     * @return ModuleClassInterface|MethodClassInterface<ModuleClassInterface>
     */
    protected function createMockWithoutAccess(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core security service class with mock too
        $helper = new ServicesHelper('services');
        $helper->createMockSecurityWithoutAccess($mock, $count);
        return $mock;
    }

    /**
     * Override redirect() method to throw exception + check if called $count times
     * @param string $modName
     * @param class-string<ModuleClassInterface|MethodClassInterface<ModuleClassInterface>> $className
     * @param int $count
     * @return ModuleClassInterface|MethodClassInterface<ModuleClassInterface>
     */
    protected function createMockWithoutRedirect(string $modName, string $className, int $count = 1): object
    {
        $args = $this->getConstructorArgs($modName, $className);
        $mock = new $className(...$args);
        // override core controller service class with mock too
        $helper = new ServicesHelper('services');
        $helper->createMockControllerWithoutRedirect($mock, $count);
        return $mock;
    }

    /**
     * Override exit() method to throw exception + check if called $count times
     * @param string $modName
     * @param class-string<ModuleClassInterface|MethodClassInterface<ModuleClassInterface>> $className
     * @param int $count
     * @return ModuleClassInterface|MethodClassInterface<ModuleClassInterface>
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
        $helper = new ServicesHelper('services');
        $helper->createMockServicesWithoutExit($mock, $count);
        return $mock;
    }
}
