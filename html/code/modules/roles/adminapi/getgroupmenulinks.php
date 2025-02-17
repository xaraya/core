<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminApi;
use xarController;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles adminapi getgroupmenulinks function
 * @extends MethodClass<AdminApi>
 */
class GetgroupmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function pass individual menu items to the main menu
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array the menulinks for the main menu items.
     * @see AdminApi::getgroupmenulinks()
     */
    public function __invoke(array $args = [])
    {

        // Security Check
        if (xarSecurity::check('AddRoles', 0)) {

            $menulinks[] = ['url'   => xarController::URL(
                'roles',
                'admin',
                'newgroup'
            ),
                'title' => xarML('Add a new user group'),
                'label' => xarML('Add')];
        }

        // Security Check
        if (xarSecurity::check('EditRoles', 0)) {

            $menulinks[] = ['url'   => xarController::URL(
                'roles',
                'admin',
                'viewallgroups'
            ),
                'title' => xarML('View and edit user groups'),
                'label' => xarML('View')];
        }


        if (empty($menulinks)) {
            $menulinks = '';
        }

        return $menulinks;
    }
}
