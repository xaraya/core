<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getmenulinks()
{
    $menulinks = array();
/* Security Check
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'));
    }*/

    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'showusers'),
                              'title' => xarML('View and edit all groups/users on the system'),
                              'label' => xarML('View Groups/Users'));
    }

    if (xarSecurityCheck('AddRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'newrole'),
                              'title' => xarML('Add a new user or group to the system'),
                              'label' => xarML('Add Group/User'));
    }

    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'createmail'),
                              'title' => xarML('Manage system emails'),
                              'label' => xarML('Email Messaging'));
    }


    if (xarSecurityCheck('DeleteRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'purge'),
                              'title' => xarML('Undelete or permanently remove users/groups'),
                              'label' => xarML('Recall/Purge'));
    }

    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'sitelock'),
                              'title' => xarML('Lock the site to all but selected users'),
                              'label' => xarML('Site Lock'));
    }

    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the roles module configuration'),
                              'label' => xarML('Modify Config'));
    }
    return $menulinks;
}

?>