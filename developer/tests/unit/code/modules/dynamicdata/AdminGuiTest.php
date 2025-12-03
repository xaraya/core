<?php

use Xaraya\Modules\TestHelper;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Services\xar;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class AdminGuiTest extends TestHelper
{
    public function testAdminGui(): void
    {
        $expected = AdminGui::class;
        $admingui = xar::mod()->getModule('dynamicdata')->admingui();
        $this->assertEquals($expected, $admingui::class);
    }

    public function testMain(): void
    {
        // we need to load templates to get some output
        xar::tpl()->init();
        xar::block()->init();
        // this is required because DD overview calls <xar:data-input type="grouplist"/> which does
        // security check in roles when getting the group list, and it redirects & exits
        xar::ctl()->setCallback('redirectTo', [$this, 'hello']);

        $context = $this->createContext();
        /** @var AdminGui $admingui */
        $admingui = $this->createMockWithAccess('dynamicdata', AdminGui::class);
        $admingui->setContext($context);

        $args = ['hello' => 'world'];
        $data = $admingui->main($args);

        xar::ctl()->setCallback('redirectTo', null);

        $expected = 'The Admin interface';
        $this->assertStringContainsString($expected, $data);
    }

    public function hello($redirectURL, $httpResponse, $context)
    {
        echo "We got: $redirectURL with " . json_encode($context);
    }
}
