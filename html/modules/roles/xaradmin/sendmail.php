<?php

function roles_admin_sendmail()
{
    // Get parameters from whatever input we need
    list($uid,
         $message,
         $subject) = xarVarCleanFromInput('uid',
                                          'message',
                                          'subject');

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;

    // Security check
    if (!xarSecurityCheck('MailRoles')) return;

    // Check arguments
    if (empty($subject)) {
        $msg = xarML('No Subject Provided for Email');
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }

    if (empty($message)) {
        $msg = xarML('No Message Provided for Email');
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }

    // Get user information
    $groups = xarModAPIFunc('roles',
                            'user',
                            'getUsers',
                            array('uid' => $uid));

    if ($groups == false) return;

    foreach ($groups as $group){

        // Get user information
        $users = xarModAPIFunc('roles',
                               'user',
                               'get',
                                array('uid' => $group['uid']));

        if ($users == false) return;

        if (!xarModAPIFunc('mail',
                           'admin',
                           'sendmail',
                           array('info'     => $users['email'],
                                 'name'     => $users['name'],
                                 'subject'  => $subject,
                                 'message'  => $message))) return;
    }


    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));

    // Return
    return true;
}
?>
