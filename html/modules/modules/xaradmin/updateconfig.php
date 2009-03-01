<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
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
    if(!xarVarFetch('disableoverview','bool', $disableoverview, false, XARVAR_NOT_REQUIRED)) return;

    xarModVars::set('modules', 'disableoverview', $disableoverview);
     // lets update status and display updated configuration
    xarResponseRedirect(xarModURL('modules', 'admin', 'modifyconfig'));

    // Return
    return true;
}

?>