<?php
/**
 * Main admin function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * the main administration function
 */
function roles_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    // success
    return true;
}
?>
