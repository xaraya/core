<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin hooks function
 * @extends MethodClass<AdminGui>
 */
class HooksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Hooks shows the configuration of hooks for other modules
     * @return array|void Returns display data array on success, null on security check failure
     * @see AdminGui::hooks()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!$this->sec()->checkAccess('ManageCategories')) {
            return;
        }

        $data = [];

        return $data;
    }
}
