<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\SessionContext;
use Xaraya\DataObject\UserApi;

//use Xaraya\Sessions\SessionHandler;

final class UserApiTest extends TestCase
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

    public function testUserApi(): void
    {
        $expected = UserApi::class;
        $userapi = xarMod::getAPI('dynamicdata');
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

    public function testUserApiTestCall(): void
    {
        $context = new Context(['source' => __METHOD__]);
        $userapi = xarMod::getAPI(modName: 'dynamicdata');
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
            'url' => 'http://localhost/index.php?module=dynamicdata&amp;type=user&amp;func=view&amp;itemtype=3',
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
            'url' => 'http://localhost/index.php?module=dynamicdata&amp;type=user&amp;func=display&amp;name=sample&amp;itemid=1',
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

        $context = new Context(['source' => __METHOD__]);
        $args = ['hello' => 'world'];
        $expected = array_merge($args, [
            'context' => $context,
            'handled' => true,
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
