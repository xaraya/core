<?php
/**
 * File: $Id$
 *
 * Main modules module function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * main modules module function
 * @return modules_admin_main
 *
 */
function modules_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }
    // success
    return true;
}

?>