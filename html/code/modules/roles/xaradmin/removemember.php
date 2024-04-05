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
 * @return string|void
 */
function roles_admin_removemember(array $args = [], $context = null)
{
    // get input from any view of this page
    if (!xarVar::fetch('parentid', 'int', $parentid, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('childid',  'int', $childid, xarVar::NOT_REQUIRED)) return;
    // call the Roles class and get the parent and child objects
    $role   = xarRoles::get($parentid);
    $member = xarRoles::get($childid);

    // Security
    if (empty($role)) return xarController::notFound(null, $context);
    if (empty($member)) return xarController::notFound(null, $context);
    if(!xarSecurity::check('RemoveRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // Check for authorization code
    if (!xarSec::confirmAuthKey()) {
        return xarController::badRequest('bad_author', $context);
    }        

    // remove the child from the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','removemember', array('id' => $childid, 'gid' => $parentid))) return;

    // call item create hooks (for DD etc.)
    $pargs['module']   = 'roles';
    $pargs['itemtype'] = $role->getType(); // we might have something separate for groups later on
    $pargs['itemid']   = $parentid;
    xarModHooks::call('item', 'unlink', $parentid, $pargs);

    // redirect to the next page
    xarController::redirect(xarController::URL('roles', 'admin', 'modify',
        array('id' => $childid)), null, $context);
    return true;
}
