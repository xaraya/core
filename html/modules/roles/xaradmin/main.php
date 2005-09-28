<?php
/**
 * File: $Id$
 *
 * Main administration function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * the main administration function
 */
function roles_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    if (xarModGetVar('adminpanels', 'overview') == 0) {
        return array();
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers'));
    }
    // success
    return true;
}
?>