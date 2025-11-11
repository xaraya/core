<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\Modules\DynamicData;

/**
 * Handle (traditional) DD test gui functions via module class
 *
 * @method mixed testServices(array $args = [])
 * @extends
 */
class TestGui extends AdminGui
{
    public function configure()
    {
        $this->setModType('test');
        // don't call xar::mod()->load() for dynamicdata test GUI
    }

    /**
     * Test method to verify that we can override checkAccess()
     * @param array<string, mixed> $args
     * @return array<mixed>|void
     */
    public function test_with_access(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('EditDynamicData')) {
            return;
        }
        return $this->main($args);
    }

    /**
     * Test method to verify that we can override redirect()
     * @param array<string, mixed> $args
     * @return array<mixed>|true
     */
    public function test_with_redirect(array $args = [])
    {
        $url = $this->mod()->getURL('admin', 'main', $args);
        $this->ctl()->redirect($url, 301);
        return true;
    }

    /**
     * Test method to verify that we can override exit()
     * @param array<string, mixed> $args
     * @return array<mixed>|void
     */
    public function test_with_exit(array $args = [])
    {
        $status = $args['status'] ?? 'Done.';
        $this->exit($status);
    }

    /**
     * Test method to verify that we can use core services
     * @param array<string, mixed> $args
     * @return array<mixed>|void
     */
    public function test_with_services(array $args = [])
    {
        $args['method'] = __METHOD__;
        $args['return_url'] = $this->mod()->getURL('test', 'other', $args);
        return $this->mod()->prepare($args);
    }
}
