<?php

/**
 * deletePrivilege - delete a privilege
 * prompts for confirmation
 */
function privileges_admin_deleteprivilege()
{
    if (!xarVarFetch('pid', 'id', $pid)) return;
    if (!xarVarFetch('confirmation', 'str', $confirmation, '')) return;

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

//Call the Privileges class and get the privilege to be deleted
    $privs = new xarPrivileges();
    $priv = $privs->getprivilege($pid);
    $name = $priv->getName();

// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',$name)) return;

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
