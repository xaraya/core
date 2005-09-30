<?php
/**
 * Utility functions for menu links
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function adminpanels_adminapi_getmenulinks()
{
    // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminPanel',0)) {
        $menulinks[] = Array('url'   => xarModURL('adminpanels',
                                                   'admin',
                                                   'modifyconfig'),
                              'title' => xarML('Modify configuration for the administration menus/views'),
                              'label' => xarML('Modify Config'));
/*        $menulinks[] = Array('url'   => xarModURL('adminpanels',*/
/*                                                   'admin',*/
/*                                                   'configoverviews'),*/
/*                              'title' => xarML('Modify configuration for the modules overviews'),*/
/*                              'label' => xarML('Overviews'));*/
/*        $menulinks[] = Array('url'   => xarModURL('adminpanels',*/
/*                                                   'admin',*/
/*                                                   'configdashboard'),*/
/*                              'title' => xarML('Modify configuration for the dashboard'),*/
/*                              'label' => xarML('Dashboard'));*/
/*        $menulinks[] = Array('url'   => xarModURL('adminpanels',*/
/*                                                   'admin',*/
/*                                                   'modifyconfig'),*/
/*                              'title' => xarML('Modify configuration for the administration menus/views'),*/
/*                              'label' => xarML('Admin Menus'));*/
    }
    return $menulinks;
}
?>
