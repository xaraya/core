<?php

function roles_admin_sendmail()
{ 
    // Get parameters from whatever input we need
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('message', 'str:1:', $message)) return;
    if (!xarVarFetch('subject', 'str:1', $subject)) return; 
    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return; 
    // Security check
    if (!xarSecurityCheck('MailRoles')) return; 
    // Get user information
    $groups = xarModAPIFunc('roles',
        'user',
        'getUsers',
        array('uid' => $uid));

    if ($groups == false) return;

    foreach ($groups as $group) {
        // Get user information
        $users = xarModAPIFunc('roles',
            'user',
            'get',
            array('uid' => $group['uid']));

        if ($users == false) return;

        if (!xarModAPIFunc('mail',
                'admin',
                'sendmail',
                array('info' => $users['email'],
                    'name' => $users['name'],
                    'subject' => $subject,
                    'message' => $message))) return;
    } 
    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles')); 
    // Return
    return true;
} 

?>