<?php
/**
 * File: $Id$
 *
 * Log user out of system
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
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
        $msg = xarML('Problem Logging Out.  Module #(1) Function #(2)', 'roles', 'logout');
        xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
        return;
    }
    xarResponseRedirect($redirecturl);
    return true;
}
?>