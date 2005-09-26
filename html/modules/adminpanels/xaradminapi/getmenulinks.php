<?php
/**
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
                              'title' => xarML('Modify configuration for the Admin Panels'),
                              'label' => xarML('Modify config'));
    }
    return $menulinks;
}
?>
