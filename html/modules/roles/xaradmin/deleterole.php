<?php
/**
 * File: $Id$
 *
 * Detele a role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */

/**
 * deleteRole - delete a role
 * prompts for confirmation
 */
function roles_admin_deleterole()
{
    // get parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;

    // Call the Roles class
    $roles = new xarRoles();
    // get the role to be deleted
    $role = $roles->getRole($uid);
    $type = $role->isUser() ? 0 : 1;
    // The user API function is called.
    $data = xarModAPIFunc('roles',
        'user',
        'get',
        array('uid' => $uid, 'type' => $type));

    if ($data == false) return;

    $name = $role->getName();

// Security Check
    if(!xarSecurityCheck('DeleteRole',0,'Roles',$name)) return;

// Prohibit removal of any groups the system needs
    if(strtolower($role->getName()) == strtolower(xarModGetVar('roles','defaultgroup'))) {
        $msg = xarML('The group #(1) is the default group for new users. If you want to remove it change the appropriate configuration setting first.', $role->getName());
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if (empty($confirmation)) {
        // Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['uid'] = $uid;
        $data['ptype'] = $role->getType();
        $data['deletelabel'] = xarML('Delete');
        $data['name'] = $name;
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;
        // Check to make sure the user is not active on the site.
        $check = xarModAPIFunc('roles',
                              'user',
                              'getactive',
                              array('uid' => $uid));

        if (empty($check)) {
            // Try to remove the role and bail if an error was thrown
            if (!$role->remove()) return;

            // call item delete hooks (for DD etc.)
// TODO: move to remove() function
            $pargs['module'] = 'roles';
            $pargs['itemtype'] = $type; // we might have something separate for groups later on
            $pargs['itemid'] = $uid;
            xarModCallHooks('item', 'delete', $uid, $data);
        } else {
            $msg = xarML('That user has a current active session', 'roles');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
    }
}

?>
