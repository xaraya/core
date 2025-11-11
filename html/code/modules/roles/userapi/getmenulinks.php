<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;

/**
 * roles userapi getmenulinks function
 * @extends MethodClass<UserApi>
 */
class GetmenulinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function pass individual menu items to the user menu.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array the menulinks for the user menu items of this module.
     * @see UserApi::getmenulinks()
     */
    public function __invoke(array $args = [])
    {
        //If we have turned on role list (memberlist) display and users have requisite level to see them
        $menulinks = [];
        if ((bool) $this->mod()->getVar('displayrolelist')) {
            $menulinks[] = [
                'url'   => $this->ctl()->getModuleURL('roles', 'user', 'view'),
                'title' => $this->ml('View All Users'),
                'label' => $this->ml('Memberslist'),
                'active' => ['view'],
            ];
        }
        if ($this->user()->isLoggedIn()) {
            $menulinks[] = [
                'url'   => $this->ctl()->getModuleURL('roles', 'user', 'account'),
                'title' => $this->ml('Your Custom Configuration'),
                'label' => $this->ml('Your Account'),
                'active' => ['account'],
            ];
        }
        return $menulinks;
    }
}
