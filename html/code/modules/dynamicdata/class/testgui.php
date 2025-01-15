<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use sys;

sys::import('modules.dynamicdata.class.admingui');

/**
 * Handle (traditional) DD test gui functions via module class
 */
class TestGui extends AdminGui
{
    /**
     * Test method to verify that we can override checkAccess()
     * @param array<string, mixed> $args
     * @return array<mixed>|void
     */
    public function test_with_access(array $args = [])
    {
        // Security
        if (!$this->checkAccess('EditDynamicData')) {
            return;
        }
        return $this->main($args);
    }

    /**
     * Test method to verify that we can override redirect()
     * @param array<string, mixed> $args
     * @return array<mixed>|void
     */
    public function test_with_redirect(array $args = [])
    {
        $url = $this->getUrl('admin', 'main', $args);
        $this->redirect($url, 301);
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
}
