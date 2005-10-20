<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
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
function adminpanels_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminPanel')) return;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $data['sortorder'] = array();
    $data['sortorder']['byname']    = xarML('By Name');
    $data['sortorder']['bycat']     = xarML('By Category');
    //$data['sortorder']['byweight']  = xarML('By Weight');
    //$data['sortorder']['bygroup']   = xarML('By Group');

    $data['menustyle']              = xarModGetVar('adminpanels', 'menustyle');
    $data['showhelp']               = xarModGetVar('adminpanels', 'showhelp');
/*     $data['submitlabel']            = xarML('Click "Submit" to change configuration:'); */

    // moved from modify overviews
    $data['showoverviews']          = xarModGetVar('adminpanels', 'overview');
    // Dashboard
    $data['dashboard']              = xarModGetVar('adminpanels', 'dashboard');
    // everything else happens in Template for now
    return $data;
}
?>
