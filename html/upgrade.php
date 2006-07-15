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
include 'includes/xarCore.php';
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

    $pageOutput = xarTpl_renderPage($mainModuleOutput,NULL,'installer');

    // Handle exceptions
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;

    echo $pageOutput;
    return true;
}
if (!xarUpgradeMain()) {

    // If we're here there must be surely an uncaught exception
    $text = xarErrorRender('template');

    // TODO: #2
    if (xarCurrentErrorID() == 'TEMPLATE_NOT_EXIST') {
        echo "<?xml version=\"1.0\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head><title>Error</title><body>$text</body></html>";
    } else {
        // It's important here to free exception before calling xarTplPrintPage
        // As we are in the exception handling phase, we can clear it without side effects.
        xarErrorFree();
        // Render page
        $pageOutput = xarTpl_renderPage($text,NULL,'installer');
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            // Fallback to raw html
            $msg = '<span style="color: #FF0000;">The current page is shown because the Blocklayout Template Engine failed to render the page, however this could be due to a problem not in BL itself but in the template. BL has raised or has left uncaught the following exception:</span>';
            $msg .= '<br /><br />';
            $msg .= xarErrorRender('rawhtml');
            $msg .= '<br />';
            $msg .= '<span style="color: #FF0000;">The following exception is instead the exception caught from the main catch clause (Please note that they could be the same if they were raised inside BL or inside the template):</span>';
            $msg .= '<br /><br />';
            $msg .= $text;
            echo "<?xml version=\"1.0\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head><title>Error</title><body>$msg</body></html>";
        } else {
            echo $pageOutput;
        }
    }
}
?>
