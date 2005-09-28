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
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * the main administration function
 *
 */
function dynamicdata_admin_main()
{
// Security Check
    if(!xarSecurityCheck('EditDynamicData')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        $data = xarModAPIFunc('dynamicdata','admin','menu');

        // Return the template variables defined in this function
        return $data;
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view'));
    }

    return true;
}

?>