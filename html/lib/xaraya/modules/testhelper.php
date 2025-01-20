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
use xarController;
use xarDatabase;
use xarLog;
use xarMod;
use xarSecurity;
use xarServer;
use xarSession;
use xarUser;
use sys;
use LogicException;
use UnauthorizedOperationException;

/**
 * TestHelper for unit testing module class & method class
 */
class TestHelper extends TestCase
{
    protected static string $oldDir;
    /** @var ?callable */
    protected $callback = null;

    public static function setUpBeforeClass(): void
    {
        // initialize bootstrap
        sys::init();
        // initialize caching - delay until we need results
        xarCache::init();
        // initialize loggers
        xarLog::init();
        // initialize database - delay until caching fails
        xarDatabase::init();
        // initialize modules
        xarMod::init();
        // initialize users
        xarUser::init();
        // use RequestContext as request handler
        xarServer::setRequestClass(RequestContext::class);
        // use SessionContext as session handler
        xarSession::setSessionClass(SessionContext::class);

        // file paths are relative to parent directory
        static::$oldDir = (string) getcwd();
        chdir(dirname(__DIR__));
    }

    public static function tearDownAfterClass(): void
    {
        // reset redirectTo callback in xarController
        xarController::setCallback('redirectTo', null);
        // use default request handler
        xarServer::setRequestClass(RequestHandler::class);
        // use default session handler
        xarSession::setSessionClass(SessionHandler::class);

        chdir(static::$oldDir);
    }

    protected function setUp(): void {}

    protected function tearDown(): void {}

    /**
     * Create context with optional arguments
     * @param array<mixed> $args
     * @return Context<string, mixed>
     */
    protected function createContext(array $args = [])
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
        // Xaraya\Modules\MyFancyModule\UserApi
        $parts = explode('\\', $className);
        array_pop($parts);
        // Xaraya\Modules\MyFancyModule\Module
        $moduleName = implode('\\', $parts) . '\Module';
        assert(is_subclass_of($moduleName, ModuleInterface::class));
        return new $moduleName($modName);
    }

    /**
     * Create parent component for a method class
     * @param string $modName
     * @param class-string<MethodServicesInterface<ModuleServicesInterface>> $className
     * @return ModuleServicesInterface
     */
    protected function createComponent(string $modName, string $className)
    {
        // Xaraya\Modules\MyFancyModule\UserApi\ViewMethod
        $parts = explode('\\', $className);
        array_pop($parts);
        // Xaraya\Modules\MyFancyModule\UserApi
        $parentName = implode('\\', $parts);
        assert(is_subclass_of($parentName, ModuleServicesInterface::class));
        return new $parentName($modName);
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
        // @todo deprecate direct method access from coretrait here - use core services below
        $args = $this->getConstructorArgs($modName, $className);
        /**
        $mock = $this->getMockBuilder($className)
            ->setConstructorArgs($args)
            ->onlyMethods(['checkAccess'])
            ->getMock();
        // override checkAccess() method to return true + check if called $count times
        $constraint = $this->atMost($count);
        $mock->expects($constraint)
            ->method('checkAccess')
            ->willReturn(true);
         */
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
        // @todo deprecate direct method access from coretrait here - use core services below
        $args = $this->getConstructorArgs($modName, $className);
        /**
        $mock = $this->getMockBuilder($className)
            ->setConstructorArgs($args)
            ->onlyMethods(['callSecurityCheck'])
            ->getMock();
        // override callSecurityCheck() method to intercept redirect + check if called $count times
        $constraint = $this->atMost($count);
        $mock->expects($constraint)
            ->method('callSecurityCheck')
            ->willReturnCallback(function ($mask, $catch = 1, $component = '', $instance = '') {
                $this->callback = xarController::getCallback('redirectTo');
                xarController::setCallback('redirectTo', [$this, 'sendRedirectToCallback']);
                $result = xarSecurity::check($mask, $catch, $component, $instance) ? true : false;
                xarController::setCallback('redirectTo', $this->callback);
                return $result;
            });
         */
        $mock = new $className(...$args);
        // override core security service class with mock too
        $helper = new ServicesHelper();
        $helper->createMockSecurityWithoutAccess($mock, $count);
        return $mock;
    }

    /**
     * Send redirect to callback in xarController::redirect()
     * @param string $redirectURL
     * @param mixed $httpResponse
     * @param mixed $context
     * @throws \UnauthorizedOperationException
     * @return never
     */
    public function sendRedirectToCallback($redirectURL, $httpResponse, $context)
    {
        xarController::setCallback('redirectTo', $this->callback);
        throw new UnauthorizedOperationException('Called redirectToCallback() for ' . $redirectURL);
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
        // @todo deprecate direct method access from coretrait here - use core services below
        $args = $this->getConstructorArgs($modName, $className);
        /**
        $mock = $this->getMockBuilder($className)
            ->setConstructorArgs($args)
            ->onlyMethods(['redirect'])
            ->getMock();
        // override redirect() method to throw exception + check if called $count times
        $constraint = $this->atMost($count);
        $mock->expects($constraint)
            ->method('redirect')
            ->willReturnCallback(function ($url) {
                throw new LogicException("Called redirect('$url')");
            });
         */
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
        // @todo deprecate direct method access from coretrait here - use core services below
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
