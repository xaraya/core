<?php
/**
 * File: $Id:
 *
 * Confirm logout from administration system
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
 * Confirm logout from administration system
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  data for template
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_admin_confirmlogout(){

    // first we display a pseudo modal-dialogue with Yes/No choice
    // if answer is Logout, we logout as the admin/user..
    // if Cancel we return to previous admin panel
    // prepare data for template
    $data = xarTplModule(
    'adminpanels',
    'admin',
    'confirmlogout',
    array(  'warning'       => xarVarPrepForDisplay(xarML('ATTENTION! YOU ARE ABOUT TO LOGOUT FROM ADMIN SYSTEM..')),
            'question'      => xarVarPrepForDisplay(xarML('Are you sure? Click "Cancel" to return to the previous page..')),
            'logouturl'     => xarModURL('roles' ,'user', 'logout', array()),
            'logoutlabel'   => xarVarPrepForDisplay(xarML('Logout')),
            'cancelurl'     => 'javascript:top.history.go(-1)',
            'cancellabel'   => xarVarPrepForDisplay(xarML('Cancel'))
            ));


    return $data;
}
?>
