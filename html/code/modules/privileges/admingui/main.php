<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use xarController;
use xarModVars;
use xarSecurity;
use xarTpl;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin main function
 * @extends MethodClass<AdminGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Main entry point for the admin interface of this module
     * This function is the default function for the admin interface, and is called whenever the module is
     * initiated with only an admin type but no func parameter passed.
     * The function displays the module's overview page, or redirects to another page if overviews are disabled.
     * @return mixed output display string or boolean true if redirected
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditPrivileges')) {
            return;
        }

        $samemodule = xarController::isRefererSameModule();

        if (((bool) xarModVars::get('modules', 'disableoverview') == false) || $samemodule) {
            $data = ['context' => $this->getContext()];
            return xarTpl::module('privileges', 'admin', 'overview', $data);
        } else {
            xarController::redirect(xarController::URL('privileges', 'admin', 'viewprivileges'), null, $this->getContext());
            return true;
        }
    }
}
