<?php
/**
 * File: $Id$
 *
 * Remove a user or group from a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * removeMember - remove a user or group from a group
 * 
 * Remove a user or group as a member of another group.
 * This is an action page..
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com> 
 * @access public 
 * @param none $ 
 * @return none 
 * @throws none
 * @todo none
 */
function roles_admin_removemember()
{ 
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return; 
    // get input from any view of this page
    if (!xarVarFetch('parentid', 'str:1:', $parentid, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('childid', 'str:1:', $childid, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return; 
    // call the Roles class and get the parent and child objects
    $roles = new xarRoles();
    $role = $roles->getRole($parentid);
    $member = $roles->getRole($childid); 
    // assign the child to the parent and bail if an error was thrown
    $removed = $role->removeMember($member);
    if (!$removed) return; 
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles',
            'admin',
            'modifyrole',
            array('uid' => $childid)));
} 

?>