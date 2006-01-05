<?php
/**
 * Log user out of system
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
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
        throw new BadParameterException(null,'Problems logging the user out of the system');
    }
    xarResponseRedirect($redirecturl);
    return true;
}
?>
