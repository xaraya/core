<?php
/**
 * File: $Id$
 *
 * Purge users by status
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * purge users by status
 * @param 'status' the status we are purging
 * @param 'confirmation' confirmation that this item can be purge
 */
function roles_admin_purge($args)
{
    // Security Check
    if(!xarSecurityCheck('DeleteRole')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('state', 'int:1:', $state, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) return;

    extract($args);


    // Check for confirmation.
    if (empty($confirmation)) {
    $data['submitlabel']    = xarML('Submit');
    $data['authid']         = xarSecGenAuthKey();

    return $data;

    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    // The API function is called
    if (!xarModAPIFunc('roles',
                       'admin',
                       'purge',
                        array('state' => $state))) return;

    xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));

    // Return
    return true;
}

?>