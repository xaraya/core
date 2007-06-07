<?php
/**
 * Add a role to a group
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
 * addmember - add a role to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['gid'] group id
 * @param $args['id'] role id
 * @return true on success, false on failure
 */
function roles_userapi_addmember($args)
{
    extract($args);

    if (!isset($gid)) throw new EmptyParameterException('gid');
    if (!isset($id)) throw new EmptyParameterException('id');

    $group = xarRoles::get($gid);
    if($group->isUser()) throw new IDNotFoundException($gid);

    $user = xarRoles::get($id);

// Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$group->getName() . ":" . $user->getName())) return;

    if (!$group->addMember($user)) return;

    return true;
}

?>
