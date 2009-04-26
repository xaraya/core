<?php
/**
 * Main administration function
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
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
    if (!xarSecurityCheck('EditMail')) return;

    if (xarModVars::get('modules', 'disableoverview') == 0){
        return xarTplModule('mail','admin','overview');
    } else {
        xarResponse::Redirect(xarModURL('mail', 'admin', 'modifyconfig'));
        return true;
    }
} 
?>
