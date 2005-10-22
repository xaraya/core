<?php
/**
 * Main admin GUI function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
 
/**
 * Main admin gui function, entry point
 * @author John Robeson
 * @author Greg Allan
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
