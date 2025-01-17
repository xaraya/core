<?php

namespace Xaraya\Services;

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use xarController;
use xarSecurity;
use LogicException;
use UnauthorizedOperationException;

/**
 * TestHelper for unit testing module class & method class
 */
class TestHelper extends TestCase
{
    /** @var ?callable */
    protected $callback = null;

    public static function setUpBeforeClass(): void {}

    public static function tearDownAfterClass(): void {}

    public function __construct($name = 'services') {
        parent::__construct($name);
    }

    protected function setUp(): void {}

    protected function tearDown(): void {}

    /**
     * Create context with optional arguments
     * @param array<mixed> $args
     * @return Context<string, mixed>
     */
    public function createContext(array $args = [])
    {
        if (empty($args)) {
            $args = ['source' => __METHOD__];
        }
        return new Context($args);
    }

    /**
     * Override checkAccess() method to return true + check if called $count times
     * @param CoreServicesInterface $parent
     * @return SecurityInterface
     */
    public function createMockSecurityWithAccess(object $parent, int $count = 1): object
    {
        $mock = $this->getMockBuilder(SecurityService::class)
            ->setConstructorArgs([$parent])
            ->onlyMethods(['checkAccess'])
            ->getMock();
        // override checkAccess() method to return true + check if called $count times
        $constraint = $this->exactly($count);
        $mock->expects($constraint)
            ->method('checkAccess')
            ->willReturn(true);
        // set mock service as new security service
        $parent->setCoreServices(['sec' => $mock]);
        return $mock;
    }

    /**
     * Override callSecurityCheck() method to intercept redirect + check if called $count times
     * @param CoreServicesInterface $parent
     * @return SecurityInterface
     */
    public function createMockSecurityWithoutAccess(object $parent, int $count = 1): object
    {
        $mock = $this->getMockBuilder(SecurityService::class)
            ->setConstructorArgs([$parent])
            ->onlyMethods(['callSecurityCheck'])
            ->getMock();
        // override callSecurityCheck() method to intercept redirect + check if called $count times
        $constraint = $this->exactly($count);
        $mock->expects($constraint)
            ->method('callSecurityCheck')
            ->willReturnCallback(function ($mask, $catch = 1, $component = '', $instance = '') {
                $this->callback = xarController::getCallback('redirectTo');
                xarController::setCallback('redirectTo', [$this, 'sendRedirectToCallback']);
                $result = xarSecurity::check($mask, $catch, $component, $instance) ? true : false;
                xarController::setCallback('redirectTo', $this->callback);
                return $result;
            });
        // set mock service as new security service
        $parent->setCoreServices(['sec' => $mock]);
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
     * @param CoreServicesInterface $parent
     * @param int $count
     * @return ControllerInterface
     */
    public function createMockControllerWithoutRedirect(object $parent, int $count = 1): object
    {
        $mock = $this->getMockBuilder(ControllerService::class)
            ->setConstructorArgs([$parent])
            ->onlyMethods(['redirect'])
            ->getMock();
        // override redirect() method to throw exception + check if called $count times
        $constraint = $this->exactly($count);
        $mock->expects($constraint)
            ->method('redirect')
            ->willReturnCallback(function ($url) {
                throw new LogicException("Called redirect('$url')");
            });
        // set mock service as new controller service
        $parent->setCoreServices(['ctl' => $mock]);
        return $mock;
    }

    /**
     * Override exit() method to throw exception + check if called $count times
     * @param CoreServicesInterface $parent
     * @param int $count
     * @return callable
     */
    public function createMockServicesWithoutExit(object $parent, int $count = 1): callable
    {
        $callable = function (int|string $status = 0) {
            throw new LogicException("Called exit('$status')");
        };
        // set callable as new "exit service"
        $parent->setCoreServices(['exit' => $callable]);
        return $callable;
    }
}
