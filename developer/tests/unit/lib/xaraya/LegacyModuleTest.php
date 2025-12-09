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
        $data = $userapi->get_recognized_events();

        $expected = [
            'all' => 'All',
            'itemcreate' => 'itemcreate',
            'itemupdate' => 'itemupdate',
            'itemdelete' => 'itemdelete',
        ];
        $this->assertEquals($expected, $data);
    }
}
