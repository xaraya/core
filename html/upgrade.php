<?php
/**
 * Xaraya Upgrade
 *
 * package upgrader
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Upgrade
 * @author mikespub <mikespub@xaraya.com>
 * @author jojodee <jojodee@xaraya.com>
 */

/** Notes for use:
 *  upgrade.php is now an entry function for the upgrade process
 *  The main upgrade functions are now kept in the installer module.
 *     installer_admin_upgrade2 function contains the main database upgrade routines
 *     installer_admin_upgrade3 function contains miscellaneous upgrade routines
 *  Please add any special notes for a special upgrade in admin-upgrade3.xd in installer.
 *  TODO: cleanup and consolidate the upgrade functions in installer
 */
/**
 * Defines for current upgrade phases
 */
define ('XARUPGRADE_PHASE_WELCOME',             '1');
define ('XARUPGRADE_DATABASE',                  '2');
define ('XARUPGRADE_MISCELLANEOUS',             '3');
define ('XARUPGRADE_PHASE_COMPLETE',            '4');
/* Show all errors by default.
 * This may be modified in xarCore.php, but gives us a good default.
 */
error_reporting(E_ALL);

// initialize the Xaraya core
include 'lib/bootstrap.php';
sys::import('xarCore');
xarCoreInit(XARCORE_SYSTEM_ALL);
/**
 * Entry function for the installer
 *
 * @access private
 * @param phase integer the install phase to load
 * @return bool true on success, false on failure
 * @todo <johnny> use non caching templates until they are set to yes
 */
function xarUpgradeMain()
{
   // let the system know that we are in the process of installing
    xarVarSetCached('Upgrade', 'upgrading',1);
    if(!xarVarFetch('upgrade_phase','int', $phase, 1, XARVAR_DONT_SET)) {return;}

    // Make sure we can render a page
    xarTplSetPageTitle(xarML('Xaraya Upgrade'));
    xarTplSetThemeName('Xaraya_Classic') or  xarCore_die('You need the Xaraya_Classic theme if you want to upgrade Xaraya.');
    // Build function name from phase
    $funcName = 'upgrade'.$phase;
      // if the debugger is active, start it
    if (xarCoreIsDebuggerActive()) {
       ob_start();
    }

    // Set the default page title before calling the module function
    xarTplSetPageTitle(xarML("Upgrading Xaraya"));

    // start the output buffer
    $mainModuleOutput =xarModFunc('installer','admin',$funcName);
  
    if (xarCoreIsDebuggerActive()) {
        if (ob_get_length() > 0) {
            $rawOutput = ob_get_contents();
            $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                 should not happen. The module is probably directly calling functions
                                 like echo, print, or printf. Please modify the module to exclude direct output.
                                 The module is violating Xaraya architecture principles.<br /><br />'.
                                 $rawOutput.
                                 '<br /><br />This is the real module output:<br /><br />'.
                                 $mainModuleOutput;
        }
        ob_end_clean();
    }

    $pageOutput = xarTpl_renderPage($mainModuleOutput,'installer');
    echo $pageOutput;
    return true;
}
// Start
// We can do this now, since we dont bubble the exceptions anymore
xarUpgradeMain();
?>
