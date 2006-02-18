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
    if (!xarVarFetch('uid', 'int:1:', $uid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'int', $itemid, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;

    $uid = isset($itemid) ? $itemid : $uid;

    // Call the Roles class
    $roles = new xarRoles();
    // get the role to be deleted
    $role = $roles->getRole($uid);
	$itemtype = $role->getType();

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
    if (!xarSecurityCheck('DeleteRole',1,'Roles',$name)) return;
    $data['frozen'] = !xarSecurityCheck('DeleteRole',0,'Roles',$name);

// Prohibit removal of any groups that have children
    if($role->countChildren()) {
        $msg = 'The group #(1) has children. If you want to remove this group you have to delete the children first.';
        throw new ForBiddenOperationException($role->getName,$msg);
    }
// Prohibit removal of any groups or users the system needs
    if($uid == xarModGetVar('roles','admin')) {
        $msg = 'The user #(1) is the designated site administrator. If you want to remove this user change the site admin in the roles configuration setting first.';
        throw new ForbiddenOperationException($role->getName,$msg);
    }
    if(strtolower($role->getName()) == strtolower(xarModGetVar('roles','defaultgroup'))) {
        $msg = 'The group #(1) is the default group for new users. If you want to remove this group change the roles configuration setting first.';
        throw new ForbiddenOperationException($role->getName(),$msg);
    }

	$types = xarModAPIFunc('roles','user','getitemtypes');
	$data['itemtypename'] = $types[$itemtype]['label'];

    if (empty($confirmation)) {
        // Load Template
		$data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
		$types = xarModAPIFunc('roles','user','getitemtypes');
		$data['itemtypename'] = $types[$itemtype]['label'];
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
            $pargs['itemtype'] = $itemtype;
            $pargs['itemid'] = $uid;
            xarModCallHooks('item', 'delete', $uid, $pargs);
        } else {
            throw new ForbiddenOperation($role->getName(),'The user "#(1)" has an active session and can not be removed at this time.');
        }
        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    }
}
?>
