<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
    if (empty($role)) return xarResponse::NotFound();
    $itemtype = $role->getType();

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    $data['object'] = $role;
    $name = $role->getName();

    // Security
    if (!xarSecurityCheck('ManageRoles',1,'Roles',$name)) return;
    
    $data['frozen'] = !xarSecurityCheck('ManageRoles',0,'Roles',$name);

    // Prohibit removal of any groups that have children
    if($role->countChildren()) {
        return xarTpl::module('roles','user','errors',array('layout' => 'remove_nonempty_group', 'user' => $role->getName()));
    }
    // Prohibit removal of any groups or users the system needs
    if($id == (int)xarModVars::get('roles','admin')) {
        return xarTpl::module('roles','user','errors',array('layout' => 'remove_siteadmin', 'user' => $role->getUName()));
    }
    if($id == (int)xarModVars::get('roles','defaultgroup')) {
        return xarTpl::module('roles','user','errors',array('layout' => 'default_usergroup', 'group' => $role->getName()));
    }

    $types = xarMod::apiFunc('roles','user','getitemtypes');
    $data['itemtypename'] = $types[$itemtype]['label'];

    if (empty($confirmation)) {
        // Load Template
        $data['itemtype'] = $itemtype;
        $types = xarMod::apiFunc('roles','user','getitemtypes');
        $data['authid'] = xarSecGenAuthKey();
        $data['id'] = $id;
        $data['ptype'] = $role->getType();
        $data['deletelabel'] = xarML('Delete');
        $data['name'] = $name;
        $data['returnurl'] = $returnurl;
        return $data;
    } else {
        if (!xarSecConfirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        
        // Check to make sure the user is not active on the site.
        $check = xarMod::apiFunc('roles',
                              'user',
                              'getactive',
                              array('id' => $id));

        if (empty($check)) {
            // Try to remove the role and bail if an error was thrown
            if (!$role->deleteItem()) return;

            // call item delete hooks (for DD etc.)
            // TODO: move to remove() function
            $pargs['exclude_module'] = array('dynamicdata');
            $pargs['module'] = 'roles';
            $pargs['itemtype'] = $itemtype;
            $pargs['itemid'] = $id;
            xarModCallHooks('item', 'delete', $id, $pargs);
        } else {
            return xarTpl::module('roles','user','errors',array('layout' => 'remove_active_session', 'user' => $role->getName()));
        }
        // redirect to the next page
        if (empty($returnurl)) {
            xarController::redirect(xarModURL('roles', 'admin', 'showusers'));
        } else {
            xarController::redirect($returnurl);
        }
        return true;
    }
}
?>
