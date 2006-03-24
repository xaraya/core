<?php
/**
 * Check privilege
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param   string privilege name privname
 * @param   string role ID uid
 * @return  bool
 */
function roles_userapi_checkprivilege($args)
{
    extract($args);

    if(!isset($privilege)) {
        $msg = xarML('roles_userapi_checkprivilege');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if (empty($uid)) $uid = xarSessionGetVar('uid');
    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    return $role->hasPrivilege($privilege);
}

?>