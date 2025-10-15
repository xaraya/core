<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\DynamicData\TestGui;

final class TestGuiTest extends TestHelper
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        xarServer::setBaseURL('http://localhost/');
    }

    public function testTestGui(): void
    {
        $expected = TestGui::class;
        $testgui = xarMod::getModule('dynamicdata')->getTestGUI();
        $this->assertEquals($expected, $testgui::class);
    }

    public function testMain(): void
    {
        $context = $this->createContext();
        $testgui = xarMod::getModule('dynamicdata')->getTestGUI();
        $testgui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $testgui->main($args);

        $expected = [
            'args' => $args,
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ];
        $this->assertEquals($expected, $data);
    }

    public function testWithServices(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = xarMod::getModule('dynamicdata')->getTestGUI();
        $testgui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $testgui->test_with_services($args);

        $expected = array_merge($args, [
            'method' => 'Xaraya\Modules\DynamicData\TestGui::test_with_services',
            'return_url' => 'http://localhost/index.php?module=dynamicdata&amp;type=test&amp;func=other&amp;hello=world',
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ]);
        $this->assertEquals($expected, $data);
    }

    public function testServicesMethod(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = xarMod::getModule('dynamicdata')->getTestGUI();
        $testgui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $testgui->test_services($args);

        $expected = array_merge($args, [
            'method' => 'Xaraya\Modules\DynamicData\TestGui\TestServicesMethod::__invoke',
            'return_url' => 'http://localhost/index.php?module=dynamicdata&amp;type=test&amp;func=other&amp;hello=world',
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ]);
        $this->assertEquals($expected, $data);
    }

    public function testClassWithAccess(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = $this->createMockWithAccess('dynamicdata', TestGui::class);
        $testgui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $testgui->test_with_access($args);

        $expected = [
            'args' => $args,
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ];
        $this->assertEquals(array_keys($expected), array_keys($data));
    }

    public function testClassWithoutAccess(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = $this->createMockWithoutAccess('dynamicdata', TestGui::class);
        $testgui->setContext($context);

        $this->expectException(UnauthorizedOperationException::class);

        $args = ['hello' => 'world'];
        $data = $testgui->test_with_access($args);

        $expected = null;
        $this->assertEquals($expected, $data);
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

        // Note: if you try to test this from a mock parent module class,
        // it will return null because it's trying to find something like
        // MockObject_TestGui_62c03933\ViewMethod as method class to __call
    }

    public function testClassWithoutRedirect(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = $this->createMockWithoutRedirect('dynamicdata', TestGui::class);
        $testgui->setContext($context);

        $this->expectException(LogicException::class);
        $expected = "Called redirect('http://localhost/index.php?module=dynamicdata&amp;type=admin&amp;func=main&amp;hello=world')";
        $this->expectExceptionMessage($expected);

        $args = ['hello' => 'world'];
        $data = $testgui->test_with_redirect($args);

        $expected = true;
        $this->assertEquals($expected, $data);
    }

    public function testClassWithoutExit(): void
    {
        $context = $this->createContext();
        /** @var TestGui $testgui */
        $testgui = $this->createMockWithoutExit('dynamicdata', TestGui::class);
        $testgui->setContext($context);

        $this->expectException(LogicException::class);
        $expected = "Called exit('Done.')";
        $this->expectExceptionMessage($expected);

        $args = ['hello' => 'world'];
        $data = $testgui->test_with_exit($args);

        $expected = null;
        $this->assertEquals($expected, $data);
    }
}
