<?php
/**
 * File: $Id
 *
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
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
    // Get parameters

    // obsolete, need to comment out or delete after upgrade..
    // but for now we just re-use it to indicate if we want a marker against active module
    if(!xarVarFetch('showmarker', 'isset', $showmarker, NULL, XARVAR_DONT_SET)) {return;}

    // type of the marker symbol(s)
    if(!xarVarFetch('marker', 'isset', $marker, '[x]', XARVAR_NOT_REQUIRED)) {return;}

    // this is actually a sort order switch, which of course affect the style of the menu
    if(!xarVarFetch('menustyle', 'isset', $menustyle, 'byname', XARVAR_NOT_REQUIRED)) {return;}

    // show or hide a link in adminmenu to administrators logout
    if(!xarVarFetch('showlogout', 'isset', $showlogout, NULL, XARVAR_DONT_SET)) {return;}
    
    // show or hide a link in adminmenu to a contectual on-line help for the active module
    if(!xarVarFetch('showhelp', 'isset', $showhelp, NULL, XARVAR_DONT_SET)) {return;}

    // enable or disable overviews
    if(!xarVarFetch('overview', 'isset', $overview, NULL, XARVAR_DONT_SET)) {return;}

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    if(!$showmarker){
        xarModSetVar('adminpanels', 'showmarker', 0);
    }else{
        xarModSetVar('adminpanels', 'showmarker', 1);
    }

    xarModSetVar('adminpanels', 'menustyle', $menustyle);

    xarModSetVar('adminpanels', 'marker', $marker);

    if(!$showlogout){
        xarModSetVar('adminpanels', 'showlogout', 0);
    }else{
        xarModSetVar('adminpanels', 'showlogout', 1);
    }
    
    if(!$showhelp){
        xarModSetVar('adminpanels', 'showhelp', 0);
    }else{
        xarModSetVar('adminpanels', 'showhelp', 1);
    }

    if ($overview !== null) {
        xarModSetVar('adminpanels', 'overview', 1);
    } else {
        xarModSetVar('adminpanels', 'overview', 0);
    }
    
    // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>
