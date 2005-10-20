<?php
/**
 * File: $Id$
 *
 * Utility function to pass individual menu items to the main menu
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