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
use xarController;
use xarModVars;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main administration function
     * This function redirects to the view categories function
     * @return bool|array|void Returns true on success, false on failure
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security check
        if (!xarSecurity::check('EditCategories')) {
            return;
        }

        $samemodule = xarController::isRefererSameModule();

        if (((bool) xarModVars::get('modules', 'disableoverview') == false) || $samemodule) {
            return [];
        } else {
            xarController::redirect(xarController::URL('categories', 'admin', 'view'), null, $this->getContext());
        }

        return true;
    }
}
