<?php
/**
 * File: $Id:
 * 
 * The main administration function
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * the main administration function - pass-thru
 */
function privileges_admin_main()
{

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        return array();
    } else {
        xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));
    }
    // success
    return true;

}


?>