<?php
/**
 * Main Administration Function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * the main administration function
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on falure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
*/
function adminpanels_admin_main()
{

    // Security Check
    if(!xarSecurityCheck('AdminPanel')) return;

    // we only really need to show the default view (overview in this case)
    if (xarModGetVar('adminpanels', 'overview') == 0){
        // display default overvieww template
        // not ideal but would do for now - doing bug #472 fixing <andyv>
        return array();
    } else {
        xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));
    }
    // success
    return true;
}

?>