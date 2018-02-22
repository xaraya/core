<?php
/**
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * removeRole - remove a role from a privilege assignment
 * prompts for confirmation
 */
function privileges_admin_removerole()
{
    // Security
    if(!xarSecurityCheck('EditPrivileges')) return;

    if (!xarVarFetch('id',          'isset', $id,          NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('roleid',       'isset', $roleid,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) {return;}
    if (empty($id)) return xarResponse::notFound();
    if (empty($roleid)) return xarResponse::notFound();

//Call the Roles class and get the role to be removed
    $role = xarRoles::get($roleid);

//Call the Privileges class and get the privilege to be de-assigned
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);


// some assignments can't be changed, for your own good
    if ((($roleid == 1) && ($id == 1)) ||
        (($roleid == 2) && ($id == 6)) ||
        (($roleid == 4) && ($id == 2)))
        {
            throw new ForbiddenOperationException(null,'This privilege cannot be removed');
        }

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

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
        if (!xarSecConfirmAuthKey()) {
            return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        //Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) {return;}

        xarSession::setVar('privileges_statusmsg', xarML('Role Removed',
                        'privileges'));

// redirect to the next page
        xarController::redirect(xarModURL('privileges',
                                 'admin',
                                 'viewroles',
                                 array('id'=>$id)));
        return true;
    }

}
?>
