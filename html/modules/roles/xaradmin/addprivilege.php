<?php
/**
 * Assign a privilege to role
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
 * addprivilege - assign a privilege to role
 * This is an action page
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_addprivilege()
{
    // get parameters
    if (!xarVarFetch('privid', 'int:1:', $privid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;

    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($roleid);

    // Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid);

    //Security Check
    if (!xarSecurityCheck('AssignPrivilege',0,'Privileges',$priv->getName())) return;

    // If this privilege is already assigned do nothing
    // Try to assign the privilege and bail if an error was thrown
    if (!$priv->isassigned($role)) {
        if (!$role->assignPrivilege($priv)) return;
    }

    // We need to tell some hooks that we are coming from the add privilege screen
    // and not the update the actual roles screen.  Right now, the keywords vanish
    // into thin air.  Bug 1960 and 3161
    xarVarSetCached('Hooks.all','noupdate',1);

// CHECKME: do we really want to do that here (other than for flushing the cache) ?
    // call update hooks and let them know that the role has changed
    $pargs['module']   = 'roles';
    $pargs['itemtype'] = $role->getType();
    $pargs['itemid']   = $roleid;
    xarModCallHooks('item', 'update', $roleid, $pargs);

    if (!xarVarFetch('return_url', 'isset', $return_url, '', XARVAR_NOT_REQUIRED)) return;

    if (empty($return_url)) {
        $return_url = xarModURL('roles',  'admin', 'showprivileges',
                                array('uid' => $roleid));
    }

    // redirect to the next page
    xarResponseRedirect($return_url);
}
?>
