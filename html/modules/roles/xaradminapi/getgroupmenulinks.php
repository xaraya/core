<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getgroupmenulinks()
{

// Security Check
    if (xarSecurityCheck('AddRole',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles',
                                                   'admin',
                                                   'newgroup'),
                              'title' => xarML('Add a new user group'),
                              'label' => xarML('Add'));
    }

// Security Check
    if (xarSecurityCheck('EditRole',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles',
                                                   'admin',
                                                   'viewallgroups'),
                              'title' => xarML('View and edit user groups'),
                              'label' => xarML('View'));
    }


    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}
?>