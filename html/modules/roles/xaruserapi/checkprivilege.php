<?php
/**
 * Check privilege
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param   string privilege name privname
 * @param   string role ID uid
 * @return  bool
 */
function roles_userapi_checkprivilege($args)
{
    extract($args);

    if(!isset($privilege)) throw new EmptyParameterException('privilege');

    if (empty($uid)) $uid = xarSessionGetVar('uid');
    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    return $role->hasPrivilege($privilege);
}

?>
