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
 * @param $args['uid'] role id
 * @return true on success, false on failure
 */
function roles_userapi_addmember($args)
{
    extract($args);

    if((!isset($gid)) || (!isset($uid))) {
        $msg = xarML('groups_userapi_addmember');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $user = $roles->getRole($uid);

// Security Check
    if(!xarSecurityCheck('AttachRole',1,'Relation',$group->getName() . ":" . $user->getName())) return;

    if (!$group->addMember($user)) return;

    // call item create hooks (for DD etc.)
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $group->getType(); // we might have something separate for groups later on
    $pargs['itemid'] = $gid;
    $pargs['uid'] = $uid;
    xarModCallHooks('item', 'create', $gid, $pargs);

    return true;
}

?>