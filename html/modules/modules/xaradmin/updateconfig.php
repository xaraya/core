<?php
/**
 * Update the configuration parameters
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
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function modules_admin_updateconfig()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;
    
    // enable or disable overviews
    if(!xarVarFetch('disableoverview','isset', $disableoverview, 0, XARVAR_DONT_SET)) return;

    xarModSetVar('modules', 'disableoverview', ($disableoverview) ? 1 : 0);

     // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('modules', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>