<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\DynamicData\UserGui;
use Xaraya\Services\xar;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class UserGuiTest extends TestHelper
{
    public function testUserGui(): void
    {
        $expected = UserGui::class;
        $usergui = xar::mod()->usergui('dynamicdata');
        $this->assertEquals($expected, $usergui::class);
    }

    public function testMain(): void
    {
        $context = $this->createContext(['source' => __METHOD__]);
        $usergui = xar::mod()->usergui('dynamicdata');
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
        //xar::mod()->init();
        // needed to initialize the template cache
        xar::tpl()->init();
        $expected = 'View Dynamic Objects';
        $output = xar::mod()->guiFunc('dynamicdata');
        $this->assertStringContainsString($expected, $output);
    }

    public function testXarModGuiFuncInvalidName(): void
    {
        // initialize modules
        //xar::mod()->init();
        // needed to initialize the template cache
        xar::tpl()->init();
        $expected = 'Function not found';
        $output = xar::mod()->guiFunc('dynamicdata', 'user', 'invalid');
        $this->assertStringContainsString($expected, $output);
    }

    public function testXarModGuiFuncInvalidType(): void
    {
        // initialize modules
        //xar::mod()->init();
        // needed to initialize the template cache
        xar::tpl()->init();
        $expected = 'Function not found';
        $output = xar::mod()->guiFunc('dynamicdata', 'oops', 'main');
        $this->assertStringContainsString($expected, $output);
    }
}
