<?php

/**
 * deletePrivilege - delete a privilege
 * prompts for confirmation
 */
function privileges_admin_deleteprivilege()
{
    if (!xarVarFetch('pid', 'id', $pid)) return;
    if (!xarVarFetch('confirmation', 'bool', $confirmation, '', XARVAR_NOT_REQUIRED)) return;

// some privileges can't be deleted, for your own good.
    if ($pid <= xarModGetVar('privileges','frozenprivileges')) {
        $msg = xarML('This privilege cannot be deleted');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('DeletePrivilege')) return;

//Call the Privileges class and get the privilege to be deleted
    $privs = new xarPrivileges();
    $priv = $privs->getprivilege($pid);
    $name = $priv->getName();

    if (empty($confirmation)) {

        //Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['pid'] = $pid;
        $data['name'] = $name;
        return $data;

    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

//Try to remove the privilege and bail if an error was thrown
    if (!$priv->remove()) return;

    xarSessionSetVar('privileges_statusmsg', xarML('Privilege Removed',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'newprivilege'));
}


?>
