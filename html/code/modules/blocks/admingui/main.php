<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminGui;
use xarController;
use xarModVars;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin main function
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
     * @author Jim McDonald
     * @author Paul Rosania
     * @return mixed output display string or boolean true if redirected
     * @see AdminGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditBlocks')) {
            return;
        }

        $samemodule = xarController::isRefererSameModule();

        if (((bool) xarModVars::get('modules', 'disableoverview') == false) || $samemodule) {
            $data = [];
            xarVar::fetch('tab', 'pre:trim:lower:str:1:', $data['tab'], '', xarVar::NOT_REQUIRED);
            $data['context'] = $this->getContext();
            return xarTpl::module('blocks', 'admin', 'overview', $data);
        } else {
            xarController::redirect(xarController::URL('blocks', 'admin', 'view_instances'), null, $this->getContext());
            return true;
        }
    }
}
