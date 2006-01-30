<?php
/**
 * Upgrade a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @author Xaraya Development Team
 * @param id the module id to upgrade
 * @returns
 * @return
 */
function modules_admin_upgrade()
{
    $success = true;

    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) {return;}

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    // TODO: give the user the opportunity to upgrade the dependancies automatically.
    try {
        xarModAPIFunc('modules', 'admin', 'verifydependency', array('regid'=>$id));
        $minfo=xarModGetInfo($id);
        //Bail if we've lost our module
        if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_UPGRADED) {
            // Upgrade module
            $upgraded = xarModAPIFunc('modules', 'admin', 'upgrade',array('regid' => $id));
        }
    } catch (Exception $e) {
        // TODO: gradually build up the handling here, for now, bail early.
        throw $e;
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
