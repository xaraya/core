<?php
/**
 * File: $Id$
 *
 * Upgrade a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @param id the module id to upgrade
 * @returns
 * @return
 */
function modules_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) {return;}

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    $minfo=xarModGetInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_UPGRADED) {
        // Upgrade module
        $upgraded = xarModAPIFunc('modules',
                                 'admin',
                                 'upgrade',
                                 array('regid' => $id));
        //throw back
        // Bug 1222: check for exceptions in the exception stack.
        // If there are any, then return NULL to display them (even if
        // the upgrade worked).
        if(!isset($upgraded) || xarExceptionMajor()) {return;}

        // Bug 1669
        // Also check if module upgrade returned false
        if (!$upgraded) {
            $msg = xarML('Module failed to upgrade');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'SYSTEM_ERROR',
                            new SystemException($msg));
            return;
        }
    }

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));

    return true;
}

?>
