<?php
/**
 * View users in group
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
 * getUsers - view users in group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] group id
 * @return $users array containing uname, uid
 */
function roles_userapi_getUsers($args)
{
    extract($args);

    if(!isset($uid)) throw new EmptyParameterException('uid');


// Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $users = $role->getUsers();

    $flatusers = array();
    foreach($users as $user) {
        $flatusers[] = array('uid' => $user->getID(),
                        'uname' => $user->getUser()
                        );
    }

    return $flatusers;
}

?>
