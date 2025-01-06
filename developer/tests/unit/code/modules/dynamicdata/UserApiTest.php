<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\SessionContext;

//use Xaraya\Sessions\SessionHandler;

final class UserApiTest extends TestCase
{
    protected static $oldDir;

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

        // file paths are relative to parent directory
        static::$oldDir = getcwd();
        chdir(dirname(__DIR__));
    }

    public static function tearDownAfterClass(): void
    {
        chdir(static::$oldDir);
    }

    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testUserApi(): void
    {
        $expected = 12;
        $userapi = xarMod::getAPI('dynamicdata');
        $itemtypes = $userapi->getItemTypes();
        $this->assertCount($expected, $itemtypes);
    }

    public function testXarModApiFunc(): void
    {
        // initialize modules
        //xarMod::init();
        $expected = [
            'label' => 'Sample Object',
            'title' => 'View Sample Object',
            'url' => 'http://localhost/index.php?module=dynamicdata&amp;type=user&amp;func=view&amp;itemtype=3',
        ];
        $result = xarMod::apiFunc('dynamicdata', 'user', 'getitemtypes');
        $this->assertEquals($expected, $result[3]);
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
