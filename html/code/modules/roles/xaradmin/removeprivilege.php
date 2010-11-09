<?php
/**
 * Remove a privilege
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * removeprivilege - remove a privilege
 * prompts for confirmation
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_removeprivilege()
{
    if (!xarVarFetch('privid',       'int:1:', $privid)) return;
    if (!xarVarFetch('roleid',       'int:1:', $roleid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;
    // Call the Roles class and get the role
    $role  = xarRoles::get($roleid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid'   => $parent->getID(),
                           'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    // Call the Privileges class and get the privilege
    $priv = xarPrivileges::getPrivilege($privid);
    // some assignments can't be removed, for your own good
    if ((($roleid == 1) && ($privid == 1)) ||
        (($roleid == 2) && ($privid == 6)) ||
        (($roleid == 4) && ($privid == 2)))
        {
            return xarTplModule('roles','user','errors',array('layout' => 'remove_privilege'));
        }

    // Security Check
    if (!xarSecurityCheck('EditRoles')) return;
    // some info for the template display
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {
        // Load Template
        $data['authid']   = xarSecGenAuthKey();
        $data['roleid']   = $roleid;
        $data['privid']   = $privid;
        $data['ptype']    = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        $data['removelabel'] = xarML('Remove');
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) {
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        }        
        // Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) return;

        // We need to tell some hooks that we are coming from the add privilege screen
        // and not the update the actual roles screen.  Right now, the keywords vanish
        // into thin air.  Bug 1960 and 3161
        xarVarSetCached('Hooks.all','noupdate',1);

// CHECKME: do we really want to do that here (other than for flushing the cache) ?
        // call update hooks and let them know that the role has changed
        $pargs['module'] = 'roles';
        $pargs['itemid'] = $roleid;
        xarModCallHooks('item', 'update', $roleid, $pargs);

        // redirect to the next page
        xarController::redirect(xarModURL('roles', 'admin', 'showprivileges', array('id' => $roleid)));
        return true;
    }
}

?>
