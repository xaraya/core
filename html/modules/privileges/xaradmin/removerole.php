<?php
/**
 *
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * removeRole - remove a role from a privilege assignment
 * prompts for confirmation
 */
function privileges_admin_removerole()
{
    if (!xarVarFetch('id',          'isset', $id,          NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('roleid',       'isset', $roleid,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) {return;}

//Call the Roles class and get the role to be removed
    $role = xarRoles::get($roleid);

//Call the Privileges class and get the privilege to be de-assigned
    sys::import('modules.privileges.class.privileges');
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($id);


// some assignments can't be changed, for your own good
    if ((($roleid == 1) && ($id == 1)) ||
        (($roleid == 2) && ($id == 6)) ||
        (($roleid == 4) && ($id == 2)))
        {
            throw new ForbiddenOperationException(null,'This privilege cannot be removed');
        }

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

// get the names of the role and privilege for display purposes
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {

        //Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['roleid'] = $roleid;
        $data['id'] = $id;
        $data['ptype'] = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        return $data;

    }
    else {

// Check for authorization code
        if (!xarSecConfirmAuthKey()) return;

        //Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) {return;}

        xarSession::setVar('privileges_statusmsg', xarML('Role Removed',
                        'privileges'));

// redirect to the next page
        xarResponseRedirect(xarModURL('privileges',
                                 'admin',
                                 'viewroles',
                                 array('id'=>$id)));
    }

}
?>
