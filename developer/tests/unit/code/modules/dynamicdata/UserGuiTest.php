<?php

use Xaraya\Modules\TestHelper;
use Xaraya\DataObject\UserGui;

final class UserGuiTest extends TestHelper
{
    public function testUserGui(): void
    {
        $expected = UserGui::class;
        $usergui = xarMod::getGUI('dynamicdata');
        $this->assertEquals($expected, $usergui::class);
    }

    public function testMain(): void
    {
        $context = $this->createContext(['source' => __METHOD__]);
        $usergui = xarMod::getGUI('dynamicdata');
        $usergui->setContext($context);

        // the method "exists" as inherited class method (case-insensitive)
        $result = $usergui->hasMethod('main', '');
        $this->assertTrue($result);

        // the method does still "exist" if called as an api function = different from api methods
        $result = $usergui->hasMethod('main', 'api');
        $this->assertTrue($result);

        $args = ['hello' => 'world'];
        $data = $usergui->main($args);

        $expected = [
            'startlist' => [],
            'update' => false,
            'context' => $context,
        ];
        $this->assertEquals(array_keys($expected), array_keys($data));

        $expected = $usergui->getContext();
        $this->assertEquals($expected, $context);
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
