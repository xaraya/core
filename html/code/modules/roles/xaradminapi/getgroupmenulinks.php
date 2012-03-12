<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array the menulinks for the main menu items.
 */
function roles_adminapi_getgroupmenulinks()
{

// Security Check
    if (xarSecurityCheck('AddRoles',0)) {

        $menulinks[] = Array('url'   => xarModURL('roles',
                                                   'admin',
                                                   'newgroup'),
                              'title' => xarML('Add a new user group'),
                              'label' => xarML('Add'));
    }

// Security Check
    if (xarSecurityCheck('EditRoles',0)) {

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