<?php
/**
 * Modify the configuration parameters
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules module
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * standard function to modify the configuration parameters
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  the data for template
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    nothing
*/
function modules_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminPanel')) return;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // Disable the overview pages?
    $data['disableoverview'] = xarModGetVar('modules', 'disableoverview');

    // everything else happens in Template for now
    return $data;
}
?>
