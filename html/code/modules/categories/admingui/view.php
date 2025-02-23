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
use xarModVars;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View admin categories
     * @return array|void Returns display data array on succes, null on failure
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        // Get parameters
        xarVar::fetch('activetab', 'isset', $activetab, 0, xarVar::NOT_REQUIRED);
        xarVar::fetch('startnum', 'isset', $data['startnum'], 1, xarVar::NOT_REQUIRED);
        xarVar::fetch('items_per_page', 'isset', $data['items_per_page'], xarModVars::get('categories', 'items_per_page'), xarVar::NOT_REQUIRED);

        // Set a fallback value in case the modvar is empty
        if (empty($data['items_per_page'])) {
            $data['items_per_page'] = 20;
        }

        // Security check
        if (!xarSecurity::check('ManageCategories')) {
            return;
        }

        $data['options'][] = ['id' => $activetab];

        return $data;
    }
}
