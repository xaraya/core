<?php

/**
 * log user out of system
 */
function roles_user_logout()
{
    // Get input parameters
    $redirecturl = xarVarCleanFromInput('redirecturl');

    // Defaults
    if (empty($redirecturl) || preg_match('/roles/',$redirecturl)) {
    $redirecturl = 'index.php';
    }

    // Log user out
    if (!xarUserLogOut()) {
        $msg = xarML('Problem Logging Out',
                    'roles');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'LOGGIN_OUT',
                     new DefaultUserException($msg));
        return;
    }

//FIXME why is this line repeated?
    xarUserLogOut();

    xarResponseRedirect($redirecturl);
    return true;
}

?>