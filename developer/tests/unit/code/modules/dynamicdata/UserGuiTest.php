<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\Context;
use Xaraya\Context\SessionContext;
use Xaraya\DataObject\UserGui;

//use Xaraya\Sessions\SessionHandler;

final class UserGuiTest extends TestCase
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

    public function testUserGui(): void
    {
        $expected = UserGui::class;
        $usergui = xarMod::getGUI('dynamicdata');
        $this->assertEquals($expected, $usergui::class);
    }

    public function testMain(): void
    {
        $context = null;
        $usergui = xarMod::getGUI('dynamicdata');
        $usergui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $usergui->main($args);

        $expected = array_merge($args, [
            'context' => $context,
            'module' => 'dynamicdata',
            'itemtype' => 0,
        ]);
        $this->assertEquals($expected, $data);
    }

    public function testXarModGuiFunc(): void
    {
        // initialize modules
        //xarMod::init();
        // needed to initialize the template cache
        xarTpl::init();
        $expected = 'View Dynamic Objects';
        $output = xarMod::guiFunc('dynamicdata');
        $this->assertStringContainsString($expected, $output);
    }

    public function testXarModGuiFuncInvalidName(): void
    {
        // initialize modules
        //xarMod::init();
        // needed to initialize the template cache
        xarTpl::init();
        $expected = 'Function not found';
        $output = xarMod::guiFunc('dynamicdata', 'user', 'invalid');
        $this->assertStringContainsString($expected, $output);
    }

    public function testXarModGuiFuncInvalidType(): void
    {
        // initialize modules
        //xarMod::init();
        // needed to initialize the template cache
        xarTpl::init();
        $expected = 'Function not found';
        $output = xarMod::guiFunc('dynamicdata', 'oops', 'main');
        $this->assertStringContainsString($expected, $output);
    }
}
