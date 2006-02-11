<?php
/**
 * Main modules module function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * main modules module function
 * @return modules_admin_main
 *
 * @author Xaraya Development Team
 */
function modules_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    if (xarModGetVar('modules', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }
    // success
    return true;
}

?>