<?php
/**
 * Delete a role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    $name = $role->getName();

// Security Check
    $data['frozen'] = !xarSecurityCheck('DeleteRole',0,'Roles',$name);

// Prohibit removal of any groups that have children
    if($role->countChildren()) {
        $msg = xarML('The group #(1) has children. If you want to remove this group you have to delete the children first.', $role->getName());
        xarErrorSet(XAR_USER_EXCEPTION,
                    'CANNOT_CONTINUE',
                     new SystemException($msg));
        return false;
    }
// Prohibit removal of any groups or users the system needs
    if($uid == xarModGetVar('roles','admin')) {
        $msg = xarML('The user #(1) is the designated site administrator. If you want to remove this user change the site admin in the roles configuration setting first.', $role->getName());
        xarErrorSet(XAR_USER_EXCEPTION,
                    'CANNOT_CONTINUE',
                     new SystemException($msg));
        return false;
    }
    if(strtolower($role->getName()) == strtolower(xarModAPIFunc('roles','user','getdefaultgroup'))) {
        $msg = xarML('The group #(1) is the default group for new users. If you want to remove this group change the roles configuration setting first.', $role->getName());
        xarErrorSet(XAR_USER_EXCEPTION,
                    'CANNOT_CONTINUE',
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
            xarModCallHooks('item', 'delete', $uid, $pargs);
        } else {
            $msg = xarML('That user has a current active session', 'roles');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }
        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    }
}
?>
