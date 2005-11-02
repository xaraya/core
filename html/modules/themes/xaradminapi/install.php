<?php
/**
 * Install a theme.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Install a theme.
 *
 * @author Marty Vance
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function themes_adminapi_install($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];


    // FIXME: check if this is necessary, it shouldn't, we should have checked it earlier
    //     if(in_array($mainId, $installed_ids)) {
    //         xarLogMessage("Already installed $mainId in this request, skipping");
    //         return true;
    //     }
    //     $installed_ids[] = $mainId;

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminTheme', 1, 'All', 'All', 'themes')) return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing theme regid (#(1)).', $mainId);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('themes', 'admin', 'checkmissing')) return;

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarTheme_noCacheState'] = true;

    // Get module information
    $modInfo = xarThemeGetInfo($mainId);
    if (!isset($modInfo)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'THEME_NOT_EXIST', new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
        return;
    }

    switch ($modInfo['state']) {
        case XARTHEME_STATE_ACTIVE:
        case XARTHEME_STATE_UPGRADED:
            //It is already installed
            return true;
        case XARTHEME_STATE_INACTIVE:
            $initialised = true;
            break;
        default:
            $initialised = false;
            break;
    }

    //Checks if the theme is already initialised
    if (!$initialised) {
        // Finally, now that dependencies are dealt with, initialize the module
        if (!xarModAPIFunc('themes', 'admin', 'initialise', array('regid' => $mainId))) {
            $msg = xarML('Unable to initialize theme "#(1)".', $modInfo['displayname']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
            return;
        }
    }

    // And activate it!
    if (!xarModAPIFunc('themes', 'admin', 'activate', array('regid' => $mainId))) {
        $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
        return;
    }
    return true;
}
?>
