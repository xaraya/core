<?php
/**
 * Update the module version in the database
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update the module version in the database
 *
 * @param 'regId' the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 *
 * @author Xaraya Development Team
 */
function modules_admin_updateversion()
{
    // Get parameters from input
    xarVarFetch('id', 'id', $regId);

    if (!isset($regId)) throw new EmptyParameterException('regid');

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
