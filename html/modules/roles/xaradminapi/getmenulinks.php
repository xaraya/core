<?php
/**
 * File: $Id$
 *
 * Utility function to pass individual menu items to main menu
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getmenulinks()
{

/* Security Check
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'));
    }*/

    // Security Check
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'showusers'),
                              'title' => xarML('View and edit all groups/users on the system'),
                              'label' => xarML('View Groups/Users'));
    }

// Security Check
    if (xarSecurityCheck('AddRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'newrole'),
                              'title' => xarML('Add a new user or group to the system'),
                              'label' => xarML('Add Group/User'));
    }

// Security Check
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'modifynotice'),
                              'title' => xarML('Manage system emails'),
                              'label' => xarML('Email Messaging'));
    }


    if (xarSecurityCheck('DeleteRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'purge'),
                              'title' => xarML('Purge users by status'),
                              'label' => xarML('Purge Users'));
    }

    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'sitelock'),
                              'title' => xarML('Lock the site to all but selected users'),
                              'label' => xarML('Site Lock'));
    }

// Security Check
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the user module configuration'),
                              'label' => xarML('Modify Config'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>
