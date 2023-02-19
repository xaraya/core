<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
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
    if (xarSecurity::check('AddRoles',0)) {

        $menulinks[] = Array('url'   => xarController::URL('roles',
                                                   'admin',
                                                   'newgroup'),
                              'title' => xarML('Add a new user group'),
                              'label' => xarML('Add'));
    }

// Security Check
    if (xarSecurity::check('EditRoles',0)) {

        $menulinks[] = Array('url'   => xarController::URL('roles',
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