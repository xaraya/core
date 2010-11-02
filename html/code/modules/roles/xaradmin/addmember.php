<?php
/**
 * Assign a user or group to a group
 *
 * @package modules
 * @subpackage roles module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
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
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // get parameters
    if (!xarVarFetch('id',    'int:1:', $id)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;
    // call the Roles class and get the parent and child objects
    $role   = xarRoles::get($roleid);
    $member = xarRoles::get($id);

    // Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // check that this assignment hasn't already been made
    if ($member->isEqual($role))
        return xarTplModule('roles','user','errors',array('layout' => 'self_assignment'));

    // check that this assignment hasn't already been made
    if ($member->isParent($role))
        return xarTplModule('roles','user','errors',array('layout' => 'duplicate_assignment'));

    // check that the parent is not already a child of the child
    if ($role->isAncestor($member))
        return xarTplModule('roles','user','errors',array('layout' => 'circular_assignment'));

    // assign the child to the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','addmember', array('id' => $id, 'gid' => $roleid))) return;

    // redirect to the next page
    xarController::redirect(xarModURL('roles', 'admin', 'modify',
            array('id' => $id)));
}

?>
