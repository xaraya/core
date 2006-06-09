<?php
/**
 * Delete a user from a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) throw new IDNotFoundException($gid,'The group with id "#(1)" was not found');

    $user = $roles->getRole($uid);
    // Fix to bug 2889 credit to Ben Page
    if(count($user->getParents()) == 1) {
        throw new ForbiddenOperationException(null,'The user has one parent group, removal is not allowed');
    }

    return $group->removeMember($user);
}
?>
