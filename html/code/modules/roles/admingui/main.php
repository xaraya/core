<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use xarController;
use xarModVars;
use xarSecurity;
use xarTpl;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin main function
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
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        $samemodule = $this->ctl()->isSameReferer();

        if (!$this->mod()->disableOverview() || $samemodule) {
            $data = ['context' => $this->getContext()];
            return $this->tpl()->module('roles', 'admin', 'overview', $data);
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'showusers'));
            return true;
        }
    }
}
