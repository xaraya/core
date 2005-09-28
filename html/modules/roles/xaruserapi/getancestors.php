<?php
/**
 * Get ancestors of a role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * getancestors - get ancestors of a role
 * @param $args['uid'] role id
 * @return $ancestors array containing name, uid
 */
function roles_userapi_getancestors($args)
{
    extract($args);

    if(!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_getancestors.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if(!xarSecurityCheck('ReadRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $ancestors = $role->getAncestors();

    $flatancestors = array();
    foreach($ancestors as $ancestor) {
        $flatancestors[] = array('uid' => $ancestor->getID(),
                        'name' => $ancestor->getName()
                        );
    }
    return $flatancestors;
}
?>