<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\DynamicData\UserApi;

final class UserApiTest extends TestHelper
{
    public function testUserApi(): void
    {
        $expected = UserApi::class;
        $userapi = xarMod::userapi('dynamicdata');
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
        $module = xarMod::getModule('dynamicdata');
        $module->setContext(null);

        $context = $this->createContext(['source' => __METHOD__]);
        $userapi = xarMod::userapi('dynamicdata');
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
        $userapi = xarMod::userapi('dynamicdata');
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
        //xarMod::init();

        // the method "exists" as inherited class method (case-insensitive)
        $callable = xarMod::getModuleClassMethod('dynamicdata', 'userapi', 'getitemtypes');
        $this->assertTrue(is_callable($callable));

        $expected = [
            'objectid' => '4',
            'name' => 'sample',
            'label' => 'Sample Object',
            'title' => 'View Sample Object',
            'url' => 'http://localhost/index.php?object=sample&amp;method=view',
        ];
        $result = xarMod::apiFunc('dynamicdata', 'user', 'getitemtypes');
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
        $result = xarMod::apiFunc('dynamicdata', 'user', 'getitemlinks', $args);
        $this->assertCount(3, $result);
        $this->assertEquals($expected, $result[1]);
    }

    public function testXarModApiFuncTestCall(): void
    {
        // initialize modules
        //xarMod::init();

        // the method "exists" even as single-method class file (converted to PascalCase)
        $callable = xarMod::getModuleClassMethod('dynamicdata', 'userapi', 'test_call');
        $this->assertTrue(is_callable($callable));

        $context = $this->createContext(['source' => __METHOD__]);
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
        $result = xarMod::apiFunc('dynamicdata', 'user', 'test_call', $args, $context);
        $this->assertEquals($expected, $result);
    }

    public function testXarModApiFuncInvalidName(): void
    {
        // initialize modules
        //xarMod::init();
        $this->expectException(FunctionNotFoundException::class);
        $expected = 'The function "dynamicdata_userapi_invalid" could not be found or not be loaded.';
        $this->expectExceptionMessage($expected);
        $result = xarMod::apiFunc('dynamicdata', 'user', 'invalid');
    }

    public function testXarModApiFuncInvalidType(): void
    {
        // initialize modules
        //xarMod::init();
        $this->expectException(FunctionNotFoundException::class);
        $expected = 'The function "dynamicdata_oopsapi_getitemtypes" could not be found or not be loaded.';
        $this->expectExceptionMessage($expected);
        $result = xarMod::apiFunc('dynamicdata', 'oops', 'getitemtypes');
    }
}
