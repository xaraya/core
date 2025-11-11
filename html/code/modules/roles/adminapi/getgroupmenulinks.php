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
        if ($this->sec()->checkAccess('AddRoles', 0)) {

            $menulinks[] = ['url'   => $this->ctl()->getModuleURL(
                'roles',
                'admin',
                'newgroup'
            ),
                'title' => $this->ml('Add a new user group'),
                'label' => $this->ml('Add')];
        }

        // Security Check
        if ($this->sec()->checkAccess('EditRoles', 0)) {

            $menulinks[] = ['url'   => $this->ctl()->getModuleURL(
                'roles',
                'admin',
                'viewallgroups'
            ),
                'title' => $this->ml('View and edit user groups'),
                'label' => $this->ml('View')];
        }


        if (empty($menulinks)) {
            $menulinks = '';
        }

        return $menulinks;
    }
}
