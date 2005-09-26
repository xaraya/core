<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * generate the common admin menu configuration
 */
function dynamicdata_adminapi_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();
    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Dynamic Data Administration');
    // Preset some status variable
    $menu['status'] = '';
    // Return the array containing the menu configuration
    return $menu;
}
?>