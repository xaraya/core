<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Delete a role
 *
 * prompts for confirmation
 */
function roles_admin_delete()
{
    if (!xarVarFetch('id', 'id', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str', $returnurl, '', XARVAR_NOT_REQUIRED)) return;

    $id = isset($itemid) ? $itemid : $id;

    // Call the Roles class
    sys::import('modules.roles.class.roles');
    // get the role to be deleted
    $role = xarRoles::get($id);
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

    if (!xarSecurityCheck('DeleteRole',1,'Roles',$name)) return;
    $data['frozen'] = !xarSecurityCheck('DeleteRole',0,'Roles',$name);

    // Prohibit removal of any groups that have children
    if($role->countChildren()) {
        $msg = 'The group #(1) has children. If you want to remove this group you have to delete the children first.';
        throw new ForBiddenOperationException($role->getName(),$msg);
    }
    // Prohibit removal of any groups or users the system needs
    if($id == xarModVars::get('roles','admin')) {
        $msg = 'The user #(1) is the designated site administrator. If you want to remove this user change the site admin in the roles configuration setting first.';
        throw new ForbiddenOperationException($role->getName(),$msg);
    }
    if($id == xarModVars::get('roles','defaultgroup')) {
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
        $data['id'] = $id;
        $data['ptype'] = $role->getType();
        $data['deletelabel'] = xarML('Delete');
        $data['name'] = $name;
        $data['returnurl'] = $returnurl;
        return $data;
    } else {
        if (!xarSecConfirmAuthKey()) return;
        // Check to make sure the user is not active on the site.
        $check = xarModAPIFunc('roles',
                              'user',
                              'getactive',
                              array('id' => $id));

        if (empty($check)) {
            // Try to remove the role and bail if an error was thrown
            if (!$role->deleteItem()) return;

            // call item delete hooks (for DD etc.)
            // TODO: move to remove() function
            $pargs['module'] = 'roles';
            $pargs['itemtype'] = $itemtype;
            $pargs['itemid'] = $id;
            xarModCallHooks('item', 'delete', $id, $pargs);
        } else {
            throw new ForbiddenOperation($role->getName(),'The user "#(1)" has an active session and can not be removed at this time.');
        }
        // redirect to the next page
        if (empty($returnurl)) {
            xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
        } else {
            xarResponseRedirect($returnurl);
        }
    }
}
?>
