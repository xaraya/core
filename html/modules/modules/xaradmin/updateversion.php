<?php
/**
 * File: $Id$
 *
 * Update the module version in the database
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Update the module version in the database
 *
 * @param 'regId' the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 *
 */
function modules_admin_updateversion()
{
    // Get parameters from input
    xarVarFetch('id', 'id', $regId);

    if (!isset($regId)) {
        $msg = xarML('Invalid module id');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
        return;
    }

    //if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Pass to API
    $updated = xarModAPIFunc('modules',
                             'admin',
                             'updateversion',
                              array('regId' => $regId));

    if (!isset($updated)) return;

    // Redirect to module list
    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>
