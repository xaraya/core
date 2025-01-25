<?php

use Xaraya\Modules\TestHelper;
use Xaraya\DataObject\AdminGui;

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
        // we need to load templates to get some output
        xarTpl::init();
        // this is required because DD overview calls <xar:data-input type="grouplist"/> which does
        // security check in roles when getting the group list, and it redirects & exits
        xarController::setCallback('redirectTo', [$this, 'hello']);

        $context = $this->createContext();
        /** @var AdminGui $testgui */
        $admingui = $this->createMockWithAccess('dynamicdata', AdminGui::class);
        $admingui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $admingui->main($args);

        xarController::setCallback('redirectTo', null);

        $expected = 'The Admin interface';
        $this->assertStringContainsString($expected, $data);
    }

    public function hello($redirectURL, $httpResponse, $context)
    {
        echo "We got: $redirectURL with " . json_encode($context);
    }
}
