<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\DynamicData\UserApi;
use Xaraya\Services\ModulesInterface;
use Xaraya\Services\xar;

final class UserApiTest extends TestHelper
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        xar::ctl()->setBaseURL('http://localhost/');
    }

    public function testUserApi(): void
    {
        $expected = UserApi::class;
        $userapi = xar::mod()->userapi('dynamicdata');
        $this->assertEquals($expected, $userapi::class);

        // the method "exists" as inherited class method (case-insensitive)
        $result = $userapi->hasMethod('getitemtypes');
        $this->assertTrue($result);

        // the method does not "exist" if called as a non-api function = different from gui methods
        $result = $userapi->hasMethod('getitemtypes', 'other');
        $this->assertFalse($result);

        $expected = [
            'objectid' => '4',
            'name' => 'sample',
            'label' => 'Sample Object',
            'title' => 'View Sample Object',
            'url' => 'http://localhost/index.php?object=sample&amp;method=view',
        ];
        $itemtypes = $userapi->getItemTypes();
        //$this->assertCount(12, $itemtypes);
        $this->assertEquals($expected, $itemtypes[3]);

        $expected = [
            'objectid' => 4,
            'name' => 'sample',
            'itemid' => 1,
            'url' => 'http://localhost/index.php?object=sample&amp;method=display&amp;itemid=1',
            'title' => 'Display Item',
            'label' => 'Johnny',
        ];
        $args = ['itemtype' => 3];
        $itemlinks = $userapi->getItemLinks($args);
        $this->assertCount(3, $itemlinks);
        $this->assertEquals($expected, $itemlinks[1]);
    }

    public function testUserApiContext(): void
    {
        // reset context of dd module class first
        $module = xar::module('dynamicdata');
        $module->setContext(null);

        $context = $this->createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        xar::setServicesContext($context);

        $userapi = xar::mod()->userapi('dynamicdata');
        $userapi->setContext($context);

        // we have the right context
        $expected = $context;
        $this->assertEquals($expected, $userapi->getContext());

        // get corresponding user GUI
        $usergui = $userapi->usergui();

        // we still have the same context
        $expected = $context;
        $this->assertEquals($expected, $usergui->getContext());

        // get admin API of another module
        $adminapi = $userapi->getModule('dynamicdata')->adminapi();

        // we still have the same context
        $expected = $context;
        $this->assertEquals($expected, $adminapi->getContext());
    }

    public function testUserApiTestCall(): void
    {
        $context = $this->createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        xar::setServicesContext($context);

        $userapi = xar::mod()->userapi('dynamicdata');
        $userapi->setContext($context);

        // we have the right component class
        $expected = UserApi::class;
        $this->assertEquals($expected, $userapi::class);

        // the method "exists" even as single-method class file (converted to PascalCase)
        $result = $userapi->hasMethod('test_call');
        $this->assertTrue($result);

        // the method does not "exist" if called as a non-api function
        $result = $userapi->hasMethod('test_call', 'other');
        $this->assertFalse($result);

        $args = ['hello' => 'world'];
        $expected = array_merge($args, [
            'context' => $context,
            'handled' => true,
            'parent' => 'Xaraya\Modules\DynamicData\UserApi',
            'other' => [
                'handled' => 'other',
                'parent' => 'Xaraya\Modules\DynamicData\Module',
            ],
        ]);
        $result = $userapi->test_call($args);
        $this->assertEquals($expected, $result);
    }

    public function testXarModApiFunc(): void
    {
        // initialize modules
        //xar::mod()->init();

        // the method "exists" as inherited class method (case-insensitive)
        $callable = xar::mod()->getModuleClassMethod('dynamicdata', 'userapi', 'getitemtypes');
        $this->assertTrue(is_callable($callable));

        $expected = [
            'objectid' => '4',
            'name' => 'sample',
            'label' => 'Sample Object',
            'title' => 'View Sample Object',
            'url' => 'http://localhost/index.php?object=sample&amp;method=view',
        ];
        $result = xar::mod()->apiFunc('dynamicdata', 'user', 'getitemtypes');
        //$this->assertCount(12, $result);
        $this->assertEquals($expected, $result[3]);

        $expected = [
            'objectid' => 4,
            'name' => 'sample',
            'itemid' => 1,
            'label' => 'Johnny',
            'title' => 'Display Item',
            'url' => 'http://localhost/index.php?object=sample&amp;method=display&amp;itemid=1',
        ];
        $args = ['itemtype' => 3];
        $result = xar::mod()->apiFunc('dynamicdata', 'user', 'getitemlinks', $args);
        $this->assertCount(3, $result);
        $this->assertEquals($expected, $result[1]);
    }

    public function testXarModApiFuncTestCall(): void
    {
        // initialize modules
        //xar::mod()->init();

        // the method "exists" even as single-method class file (converted to PascalCase)
        $callable = xar::mod()->getModuleClassMethod('dynamicdata', 'userapi', 'test_call');
        $this->assertTrue(is_callable($callable));

        $context = $this->createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        xar::setServicesContext($context);

        $args = ['hello' => 'world'];
        $expected = array_merge($args, [
            'context' => $context,
            'handled' => true,
            'parent' => 'Xaraya\Modules\DynamicData\UserApi',
            'other' => [
                'handled' => 'other',
                'parent' => 'Xaraya\Modules\DynamicData\Module',
            ],
        ]);
        $result = xar::mod()->apiFunc('dynamicdata', 'user', 'test_call', $args, $context);
        $this->assertEquals($expected, $result);
    }

    public function testXarModApiFuncInvalidName(): void
    {
        // initialize modules
        //xar::mod()->init();
        $this->expectException(FunctionNotFoundException::class);
        $expected = 'The function "dynamicdata_userapi_invalid" could not be found or not be loaded.';
        $this->expectExceptionMessage($expected);
        $result = xar::mod()->apiFunc('dynamicdata', 'user', 'invalid');
    }

    public function testXarModApiFuncInvalidType(): void
    {
        // initialize modules
        //xar::mod()->init();
        $this->expectException(FunctionNotFoundException::class);
        $expected = 'The function "dynamicdata_oopsapi_getitemtypes" could not be found or not be loaded.';
        $this->expectExceptionMessage($expected);
        $result = xar::mod()->apiFunc('dynamicdata', 'oops', 'getitemtypes');
    }

    public function testModuleService(): void
    {
        $context = $this->createContext(['source' => __METHOD__]);
        // set context for core services here first + return static services class
        xar::setServicesContext($context);

        $userapi = xar::mod()->userapi('dynamicdata');
        $userapi->setContext($context);

        /** @var ModulesInterface $service1 */
        $service1 = $userapi->mod();
        $expected = 'dynamicdata';
        $this->assertEquals($expected, $service1->getModName());

        /** @var ModulesInterface $service2 */
        $service2 = $userapi->mod('themes');
        $expected = 'themes';
        $this->assertEquals($expected, $service2->getModName());

        /** @var ModulesInterface $service3 */
        $service3 = $userapi->mod('themes');
        $this->assertEquals($service2, $service3);

        $context1 = $service1->getContext();
        $this->assertEquals($context, $context1);
        $context2 = $service2->getContext();
        $this->assertEquals($context, $context2);

        $expected = 'Your Site Slogan';
        $result = $userapi->mod('themes')->getVar('SiteSlogan');
        $this->assertEquals($expected, $result);

        $expected = null;
        $result = $userapi->mod()->getVar('SiteSlogan');
        $this->assertEquals($expected, $result);

        $expected = 'module_settings';
        $result = $userapi->mod()->getVar('dd_objects');
        $this->assertStringContainsString($expected, $result);
    }
}
