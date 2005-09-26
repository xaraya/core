<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * addMember - assign a user or group to a group
 *
 * Make a user or group a member of another group.
 * This is an action page..
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param none $
 * @return none
 * @throws none
 * @todo none
 */
function roles_admin_addmember()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;
    // get parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;
    // call the Roles class and get the parent and child objects
    $roles  = new xarRoles();
    $role   = $roles->getRole($roleid);
    $member = $roles->getRole($uid);

    // Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // check that this assignment hasn't already been made
    if ($member->isEqual($role)) {
        $msg = xarML('This assignment is not possible');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }
    // check that this assignment hasn't already been made
    if ($member->isParent($role)) {
        $msg = xarML('This assignment already exists');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }
    // check that the parent is not already a child of the child
    if ($role->isAncestor($member)) {
        $msg = xarML('Cannot make this assignment');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }
    // assign the child to the parent and bail if an error was thrown
    if (!xarModAPIFUnc('roles','user','addmember', array('uid' => $uid, 'gid' => $roleid))) return;

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles',
            'admin',
            'modifyrole',
            array('uid' => $uid)));
}

?>