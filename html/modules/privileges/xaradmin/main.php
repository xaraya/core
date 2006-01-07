<?php
/**
 * Main administration function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * the main administration function - pass-thru
 */
function privileges_admin_main()
{

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

    if (xarModGetVar('modules', 'disableoverview') == 0){
        return array();
    } else {
        xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));
    }
    // success
    return true;

}


?>
