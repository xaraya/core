<?php
/**
 * Main administration function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */

/**
 * the main administration function
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on falure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
*/
function mail_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('EditMail')) return;

    xarResponseRedirect(xarModURL('mail', 'admin', 'modifyconfig'));

    // success
    return true;
} 
?>
