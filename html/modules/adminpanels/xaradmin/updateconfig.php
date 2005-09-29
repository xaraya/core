<?php
/**
 * Update the configuration parameters
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
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_admin_updateconfig()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;
    
    // Get parameters

    // this is actually a sort order switch, which of course affect the style of the menu
    if(!xarVarFetch('menustyle', 'isset', $menustyle, 'byname', XARVAR_NOT_REQUIRED)) {return;}

    // show or hide a link in adminmenu to a contectual on-line help for the active module
    if(!xarVarFetch('showhelp', 'isset', $showhelp, false, XARVAR_DONT_SET)) {return;}

    // enable or disable overviews
    if(!xarVarFetch('overview', 'isset', $overview, 0, XARVAR_DONT_SET)) {return;}

    // enable or disable overviews
    if(!xarVarFetch('dashboard', 'isset', $dashboard, 0, XARVAR_DONT_SET)) {return;}

    xarModSetVar('adminpanels', 'menustyle', $menustyle);
    xarModSetVar('adminpanels', 'showhelp', (!$showhelp) ? 1 : 0);
    xarModSetVar('adminpanels', 'overview', ($overview) ? 1 : 0);
    xarModSetVar('adminpanels', 'dashboard', ($dashboard) ? 1 : 0);
    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>
