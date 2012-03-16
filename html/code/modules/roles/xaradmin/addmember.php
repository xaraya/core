<?php
/**
 * Assign a user or group to a group
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
 * @return none
 */
function roles_admin_addmember()
{
    // get parameters
    if (!xarVarFetch('id',    'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (empty($roleid)) return xarResponse::notFound();
    // call the Roles class and get the parent and child objects
    $role   = xarRoles::get($roleid);
    $member = xarRoles::get($id);

    // Security
    if(!xarSecurityCheck('AttachRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // check that this assignment hasn't already been made
    if ($member->isEqual($role))
        return xarTpl::module('roles','user','errors',array('layout' => 'self_assignment'));

    // check that this assignment hasn't already been made
    if ($member->isParent($role))
        return xarTpl::module('roles','user','errors',array('layout' => 'duplicate_assignment'));

    // check that the parent is not already a child of the child
    if ($role->isAncestor($member))
        return xarTpl::module('roles','user','errors',array('layout' => 'circular_assignment'));

    // assign the child to the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','addmember', array('id' => $id, 'gid' => $roleid))) return;

    // redirect to the next page
    xarController::redirect(xarModURL('roles', 'admin', 'modify',
            array('id' => $id)));
    return true;
}

?>
