<?php
/**
 * Generate all groups listing
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getallgroups - generate all groups listing.
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param none
 * @return groups listing of available groups
 */
function roles_adminapi_getallgroups()
{
// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $groups = xarModAPIFunc('roles','user','getallgroups');
    return $groups;
}


?>
