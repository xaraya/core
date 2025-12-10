<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\LegacyModule;
use Xaraya\Modules\LegacyModuleClass;
use Xaraya\Services\xar;

final class LegacyModuleTest extends TestHelper
{
    public function testLegacyModule(): void
    {
        $xar = xar::getServicesClass();
        $module = new LegacyModule('pubsub', $xar->getContext(), $xar);

        $expected = LegacyModule::class;
        $this->assertEquals($expected, $module::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $module->getModName());

        $expected = 'user';
        $modType = 'UserGui';
        $this->assertEquals($expected, $module->getClassType($modType));
        $expected = 'userapi';
        $modType = 'UserApi';
        $this->assertEquals($expected, $module->getClassType($modType));

        $expected = 'xar_pubsub_templates';
        $this->assertContains($expected, $module->getTables());
    }

    public function testInvalidModule(): void
    {
        $xar = xar::getServicesClass();
        $module = new LegacyModule('invalid', $xar->getContext(), $xar);

        $expected = LegacyModule::class;
        $this->assertEquals($expected, $module::class);
        $expected = 'invalid';
        $this->assertEquals($expected, $module->getModName());

        // all is fine until we actually want to use it - no component
        $userapi = $module->userapi();
        $expected = null;
        $this->assertEquals($expected, $userapi);
        // $found = $userapi->hasMethod('getitemtypes');

        // all is fine until we actually want to use it - no callable method
        $callable = $module->getCallableMethod('userapi', 'getitemtypes');
        $this->assertEmpty($callable);

        // all is fine until we actually want to use it - no state
        $this->expectException(ModuleNotFoundException::class);
        $expected = 'The module "invalid" cannot be found.';
        $this->expectExceptionMessage($expected);

        $state = $module->checkState();
        $expected = [];
        $this->assertEquals($expected, $state);
    }

    protected function getTestModule()
    {
        $xar = xar::getServicesClass();
        return new LegacyModule('pubsub', $xar->getContext(), $xar);
    }

    public function testLegacyModuleClass(): void
    {
        $module = $this->getTestModule();
        $usergui = $module->getComponent('UserGui');

        $expected = LegacyModuleClass::class;
        $this->assertEquals($expected, $usergui::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $usergui->getModName());
        $expected = 'user';
        $this->assertEquals($expected, $usergui->getModType());

        // in separate xaruser/subscribe.php file
        $expected = true;
        $this->assertEquals($expected, $usergui->hasMethod('subscribe', ''));
        // in shared xaruser.php file
        $expected = true;
        $this->assertEquals($expected, $usergui->hasMethod('main', ''));
        // invalid function
        $expected = false;
        $this->assertEquals($expected, $usergui->hasMethod('invalid', ''));
        // in separate xaruser/subscribe.php file called as 'api'
        $expected = true;
        $this->assertEquals($expected, $usergui->hasMethod('subscribe', 'api'));
    }

    public function testGetUserGui(): void
    {
        $expected = LegacyModuleClass::class;
        $usergui = $this->getTestModule()->usergui();
        $this->assertEquals($expected, $usergui::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $usergui->getModName());
        $expected = 'user';
        $this->assertEquals($expected, $usergui->getModType());
    }

    public function testGetUserApi(): void
    {
        $expected = LegacyModuleClass::class;
        $userapi = $this->getTestModule()->userapi();
        $this->assertEquals($expected, $userapi::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $userapi->getModName());
        $expected = 'userapi';
        $this->assertEquals($expected, $userapi->getModType());
    }

    public function testGetAdminGui(): void
    {
        $expected = LegacyModuleClass::class;
        $admingui = $this->getTestModule()->admingui();
        $this->assertEquals($expected, $admingui::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $admingui->getModName());
        $expected = 'admin';
        $this->assertEquals($expected, $admingui->getModType());
    }

    public function testGetAdminApi(): void
    {
        $expected = LegacyModuleClass::class;
        $adminapi = $this->getTestModule()->adminapi();
        $this->assertEquals($expected, $adminapi::class);
        $expected = 'pubsub';
        $this->assertEquals($expected, $adminapi->getModName());
        $expected = 'adminapi';
        $this->assertEquals($expected, $adminapi->getModType());
    }

    public function testUserGuiMain(): void
    {
        $context = $this->createContext();
        $usergui = $this->getTestModule()->usergui();
        $usergui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $usergui->main($args);

        $expected = 'This module has no user interface *except* via display hooks';
        $this->assertEquals($expected, $data);
    }

    public function testUserGuiUsermenu(): void
    {
        $context = $this->createContext();
        $usergui = $this->getTestModule()->usergui();
        $usergui->setContext($context);
        xar::tpl()->init();

        $args = ['phase' => 'menu'];
        $data = $usergui->usermenu($args);

        $expected = 'Subscriptions';
        $this->assertStringContainsString($expected, $data);
    }

    public function testUserApiGetRecognizedEvents(): void
    {
        $context = $this->createContext();
        $userapi = $this->getTestModule()->userapi();
        $userapi->setContext($context);

        $expected = true;
        $this->assertEquals($expected, $userapi->hasMethod('get_recognized_events', 'api'));
        $args = ['hello' => 'world'];
        $data = $userapi->get_recognized_events($args);

        $expected = [
            'all' => 'All',
            'itemcreate' => 'itemcreate',
            'itemupdate' => 'itemupdate',
            'itemdelete' => 'itemdelete',
        ];
        $this->assertEquals($expected, $data);
    }

    public function testFunctionWithCamelCase(): void
    {
        $context = $this->createContext();
        $userapi = $this->getTestModule()->userapi();
        $userapi->setContext($context);

        $expected = true;
        $this->assertEquals($expected, $userapi->hasMethod('getRecognizedEvents', 'api'));
        $args = ['hello' => 'world'];
        $data = $userapi->getRecognizedEvents($args);

        $expected = [
            'all' => 'All',
            'itemcreate' => 'itemcreate',
            'itemupdate' => 'itemupdate',
            'itemdelete' => 'itemdelete',
        ];
        $this->assertEquals($expected, $data);
    }

    public function testFunctionInvalid(): void
    {
        $context = $this->createContext();
        $userapi = $this->getTestModule()->userapi();
        $userapi->setContext($context);

        $expected = false;
        $this->assertEquals($expected, $userapi->hasMethod('invalid', 'api'));

        $this->expectException(FunctionNotFoundException::class);
        $expected = 'The function "pubsub_userapi_invalid" could not be found or not be loaded.';
        $this->expectExceptionMessage($expected);

        $args = ['hello' => 'world'];
        $data = $userapi->invalid($args);
    }
}
