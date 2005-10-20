<?php
/**
 * File: $Id
 *
 * Main admin gui function, entry point
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage base
 * @author John Robeson
 * @author Greg Allan
 */
/**
 * Main admin gui function, entry point
 *
 * @return bool
 */
function base_admin_main()
{
// Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'sysinfo'));
    }
    // success
    return true;
}

?>