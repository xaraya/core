<?php
/**
 * File: $Id:
 *
 * Utility function pass individual menu items to the main menu
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function privileges_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('EditPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewprivileges&phase=active'),
                              'title' => xarML('View all privileges on the system'),
                              'label' => xarML('View Privileges'));
    }

    if (xarSecurityCheck('AssignPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'newprivilege'),
                              'title' => xarML('Add a new privilege to the system'),
                              'label' => xarML('Add Privilege'));
    }

    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the privileges module configuration'),
                              'label' => xarML('Modify Config'));
    }
    return $menulinks;
}

?>