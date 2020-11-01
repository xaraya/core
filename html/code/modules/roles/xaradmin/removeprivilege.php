<?php
/**
 * Remove a privilege
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * removeprivilege - remove a privilege
 * prompts for confirmation
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_removeprivilege()
{
    // Security
    if (!xarSecurity::check('EditRoles')) return;
    
    if (!xarVar::fetch('privid',       'int:1:', $privid)) return;
    if (!xarVar::fetch('roleid',       'int:1:', $roleid)) return;
    if (!xarVar::fetch('confirmation', 'str:1:', $confirmation, '', xarVar::NOT_REQUIRED)) return;
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
            return xarTpl::module('roles','user','errors',array('layout' => 'remove_privilege'));
        }

    // some info for the template display
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {
        // Load Template
        $data['authid']   = xarSec::genAuthKey();
        $data['roleid']   = $roleid;
        $data['privid']   = $privid;
        $data['ptype']    = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        $data['removelabel'] = xarML('Remove');
        return $data;
    } else {
        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        
        // Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) return;

        // We need to tell some hooks that we are coming from the add privilege screen
        // and not the update the actual roles screen.  Right now, the keywords vanish
        // into thin air.  Bug 1960 and 3161
        xarVar::setCached('Hooks.all','noupdate',1);

// CHECKME: do we really want to do that here (other than for flushing the cache) ?
        // call update hooks and let them know that the role has changed
        $pargs['module'] = 'roles';
        $pargs['itemid'] = $roleid;
        xarModHooks::call('item', 'update', $roleid, $pargs);

        // redirect to the next page
        xarController::redirect(xarController::URL('roles', 'admin', 'showprivileges', array('id' => $roleid)));
        return true;
    }
}

?>
