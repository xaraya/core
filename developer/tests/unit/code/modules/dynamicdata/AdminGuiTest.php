<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\SessionContext;
use Xaraya\DataObject\AdminGui;

//use Xaraya\Sessions\SessionHandler;

final class AdminGuiTest extends TestCase
{
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
        //xarMod::init();
        // initialize users
        //xarUser::init();
        xarSession::setSessionClass(SessionContext::class);
    }

    public static function tearDownAfterClass(): void {}

    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testAdminGui(): void
    {
        $expected = AdminGui::class;
        $admingui = xarMod::getModule('dynamicdata')->getAdminGUI();
        $this->assertEquals($expected, $admingui::class);
    }

    public function testMain(): void
    {
        $context = null;
        $admingui = xarMod::getModule('dynamicdata')->getAdminGUI();
        $admingui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $admingui->main($args);

        $expected = [
            'args' => $args,
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ];
        $this->assertEquals($expected, $data);
    }

    protected function createMockClassWithAccess(string $modName, string $className, int $count = 1): object
    {
        //$gui = xarMod::getModule($modName)->getGUI();
        //$gui = xarMod::getModule($modName)->getAdminGUI();
        $gui = $this->getMockBuilder($className)
            ->setConstructorArgs([$modName])
            ->onlyMethods(['checkAccess'])
            ->getMock();
        // override checkAccess() method to return true + check if called $count times
        $constraint = $this->exactly($count);
        $gui->expects($constraint)
            ->method('checkAccess')
            ->willReturn(true);
        return $gui;
    }

    public function testClassWithAccess(): void
    {
        $context = null;
        /** @var AdminGui $admingui */
        $admingui = $this->createMockClassWithAccess('dynamicdata', AdminGui::class);
        $admingui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $admingui->test_with_access($args);

        $expected = [
            'args' => $args,
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ];
        $this->assertEquals(array_keys($expected), array_keys($data));
    }

    public function testMethodWithAccess()
    {
        $this->markTestSkipped('No method class file with checkAccess() yet - see mime module');
        //$context = null;
        ///** @var SomeMethod $method */
        //$method = $this->createMockClassWithAccess('dynamicdata', SomeMethod::class);
        //$method->setContext($context);

        // use __invoke() here
        //$args = ['hello' => 'world'];
        //$data = $method($args);
    }
}
