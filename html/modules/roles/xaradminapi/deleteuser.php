<?php
/**
 * Delete a user from a group
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
 * deleteuser - delete a user from a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gid'] group id
 * @param $args['uid'] user id
 * @return true on success, false on failure
 */
function roles_adminapi_deleteuser($args)
{
    extract($args);

    if((!isset($gid)) && (!isset($uid))) throw new EmptyParameterException('gid or uid');

    if(!xarSecurityCheck('DeleteRole')) return;

    $group = xarRoles::getRole($gid);
    if($group->isUser()) throw new IDNotFoundException($gid,'The group with id "#(1)" was not found');

    $user = xarRoles::getRole($uid);
    // Fix to bug 2889 credit to Ben Page
    if(count($user->getParents()) == 1) {
        throw new ForbiddenOperationException(null,'The user has one parent group, removal is not allowed');
    }

    return $group->removeMember($user);
}
?>
