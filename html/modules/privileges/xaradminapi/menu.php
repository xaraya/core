<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * generate the common admin menu configuration
 */
function privileges_adminapi_menu()
{
    // Initialise the array that will hold the menu configuration
    $menu = array();

    // Specify the menu title to be used in your blocklayout template
    $menu['menutitle'] = xarML('Privileges Administration');

    // Preset some status variable
    $menu['status'] = '';

    // Return the array containing the menu configuration
    return $menu;
}

?>