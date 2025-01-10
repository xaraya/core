<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject;

use Xaraya\DataObject\Traits\AdminGuiInterface;
use Xaraya\DataObject\Traits\AdminGuiTrait;
use sys;

sys::import('modules.dynamicdata.class.traits.admingui');

/**
 * Handle (traditional) DD admin gui functions via module class
 * Note: this does not replace the object-centric UI handlers or direct use of object methods
 */
class AdminGui implements AdminGuiInterface
{
    /** @use AdminGuiTrait<Module> */
    use AdminGuiTrait;

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
}
