<?php
/**
 * Remove a user or group from a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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
    if (!xarVarFetch('parentid', 'int', $parentid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('childid', 'int', $childid, XARVAR_NOT_REQUIRED)) return;
    // call the Roles class and get the parent and child objects
    $roles = new xarRoles();
    $role = $roles->getRole($parentid);
    $member = $roles->getRole($childid);

    // Security Check
    if(!xarSecurityCheck('RemoveRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // remove the child from the parent and bail if an error was thrown
    if (!xarModAPIFUnc('roles','user','removemember', array('uid' => $childid, 'gid' => $parentid))) return;

    // call item create hooks (for DD etc.)
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $role->getType(); // we might have something separate for groups later on
    $pargs['itemid'] = $parentid;
    xarModCallHooks('item', 'unlink', $parentid, $pargs);

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles',
            'admin',
            'modifyrole',
            array('uid' => $childid)));
}
?>
