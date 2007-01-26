<?php
/**
 * Delete a group & info
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
 * deletegroup - delete a group & info
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_deletegroup($args)
{
    extract($args);

    if(!isset($uid)) throw new EmptyParameterException('uid');

// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $role = xarRoles::getRole($uid);

   // Prohibit removal of any groups the system needs
   $defaultgroup = xarModGetVar('roles','defaultgroup');

    if($role->getName() == $defaultgroup) {
        throw new ForbiddenOperationException($defaultgroup,'The group #(1) is the default group for new users. If you want to remove it change the appropriate configuration setting first.');
    }

// OK, go ahead
    return $role->remove();
}

?>
