<?php
/**
 * Checks missing themes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Checks missing themes, and updates the status of them if any is found
 *
 * @author Marty Vance
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @raise NO_PERMISSION
 */
function themes_adminapi_checkmissing()
{
    static $check = false;

    //Now with dependency checking, this function may be called multiple times
    //Let's check if it already return ok and stop the processing here
    if ($check) {return true;}

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminTheme',1,'All','All','themes')) return;

    //Get all modules in the filesystem
    $fileThemes = xarModAPIFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all modules in DB
    $dbThemes = xarModAPIFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;

    // See if we have lost any modules since last generation
    foreach ($dbThemes as $name => $themeinfo) {

        //TODO: Add check for any module that might depend on this one
        // If found, change its state to something inoperative too
        // New state? XAR_MODULE_DEPENDENCY_MISSING?

        if (empty($fileThemes[$name])) {
            // Old module

            // Get module ID
            $regId = $themeinfo['regid'];
            // Set state of module to 'missing'
            switch ($themeinfo['state']) {
                case XARMOD_STATE_UNINITIALISED:
                    $newstate = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
                    break;
                case XARMOD_STATE_INACTIVE:
                    $newstate = XARMOD_STATE_MISSING_FROM_INACTIVE;
                    break;
                case XARMOD_STATE_ACTIVE:
                    $newstate = XARMOD_STATE_MISSING_FROM_ACTIVE;
                    break;
                case XARMOD_STATE_UPGRADED:
                    $newstate = XARMOD_STATE_MISSING_FROM_UPGRADED;
                    break;
            }
            if (isset($newstate)) {
                $set = xarModAPIFunc('themes',
                                    'admin',
                                    'setstate',
                                    array('regid'=> $regId,
                                          'state'=> $newstate));
            }
        }
    }
    $check = true;
    return true;
}
?>
