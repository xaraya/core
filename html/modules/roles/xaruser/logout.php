<?php

/**
 * log user out of system
 */
function roles_user_logout()
{
    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:100',$redirecturl,'index.php',XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/roles/',$redirecturl)) {
        $redirecturl = 'index.php';
    }

    // Log user out
    if (!xarUserLogOut()) {
        $msg = xarML('Problem Logging Out',
                    'roles');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'LOG_OUT',
                     new DefaultUserException($msg));
        return;
    }

    xarResponseRedirect($redirecturl);
    return true;
}

?>