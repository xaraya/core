<?php
/**
 * Remove a user or group from a group
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * removeMember - remove a user or group from a group
 *
 * Remove a user or group as a member of another group.
 * This is an action page..
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return void
 */
function roles_admin_removemember()
{
    // get input from any view of this page
    if (!xarVarFetch('parentid', 'int', $parentid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('childid',  'int', $childid, XARVAR_NOT_REQUIRED)) return;
    // call the Roles class and get the parent and child objects
    $role   = xarRoles::get($parentid);
    $member = xarRoles::get($childid);

    // Security
    if (empty($role)) return xarResponse::NotFound();
    if (empty($member)) return xarResponse::NotFound();
    if(!xarSecurityCheck('RemoveRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // remove the child from the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','removemember', array('id' => $childid, 'gid' => $parentid))) return;

    // call item create hooks (for DD etc.)
    $pargs['module']   = 'roles';
    $pargs['itemtype'] = $role->getType(); // we might have something separate for groups later on
    $pargs['itemid']   = $parentid;
    xarModCallHooks('item', 'unlink', $parentid, $pargs);

    // redirect to the next page
    xarController::redirect(xarModURL('roles', 'admin', 'modify',  array('id' => $childid)));
    return true;
}
?>
