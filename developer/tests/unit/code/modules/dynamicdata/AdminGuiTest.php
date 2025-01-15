<?php

use Xaraya\Modules\TestHelper;
use Xaraya\DataObject\AdminGui;

//use Xaraya\Sessions\SessionHandler;

final class AdminGuiTest extends TestHelper
{
    public function testAdminGui(): void
    {
        $expected = AdminGui::class;
        $admingui = xarMod::getModule('dynamicdata')->getAdminGUI();
        $this->assertEquals($expected, $admingui::class);
    }

    public function testMain(): void
    {
        $context = null;
        $admingui = xarMod::getModule('dynamicdata')->getAdminGUI();
        $admingui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $admingui->main($args);

        $expected = [
            'args' => $args,
            'module' => 'dynamicdata',
            'itemtype' => 0,
            'context' => $context,
        ];
        $this->assertEquals($expected, $data);
    }
}
