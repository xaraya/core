<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @author random <marcinmilan@xaraya.com>
 */
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * display an item in a template
 *
 * @param $args array containing the item or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function roles_userapi_showdisplay($args)
{
    return xarModFunc('roles','user','display',$args);
}

?>
