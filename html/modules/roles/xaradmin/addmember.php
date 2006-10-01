<?php
/**
 * Assign a user or group to a group
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
    if (!xarVarFetch('uid',    'int:1:', $uid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;
    // call the Roles class and get the parent and child objects
    $roles  = new xarRoles();
    $role   = $roles->getRole($roleid);
    $member = $roles->getRole($uid);

    // Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // check that this assignment hasn't already been made
    if ($member->isEqual($role)) throw new ForbiddenOperationException(null,'This assignment is not possible');

    // check that this assignment hasn't already been made
    if ($member->isParent($role)) throw new DuplicateException(array('role assignment','below the specified parent'));

    // check that the parent is not already a child of the child
    if ($role->isAncestor($member)) throw new ForbiddenOperation(null,'The parent is already a child of the specified child');

    // assign the child to the parent and bail if an error was thrown
    if (!xarModAPIFUnc('roles','user','addmember', array('uid' => $uid, 'gid' => $roleid))) return;

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole',
            array('uid' => $uid)));
}

?>
