<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

// INSTALLER THEME
define('XARINSTALL_THEME','installer');


// 1. select language
// ---set language
// 2. read license agreement
// ---check agreement state
// 3. set config.php permissions
// ---check permissions
// 4. input database information
// ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
// 5. create administrator
// ---modify administrator information in nuke_users
// 6. pick optional components
// ---call optional components' init funcs, disable non-reusable areas of install module
// 7. finished!

include 'includes/xarCore.php';
// Include extra functions
include 'modules/installer/xarfunctions.php';

// Install Phases
define ('XARINSTALL_PHASE_WELCOME',             '1');
define ('XARINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('XARINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
/*TODO: rename to XARINSTALL_PHASE_SYSTEM_CHECK unless we want to implement another
phase for php settings check ..magic quotes, register globals, etc..
*/
define ('XARINSTALL_PHASE_PERMISSIONS_CHECK',   '4');
define ('XARINSTALL_PHASE_SETTINGS_COLLECTION', '5');
// FIXME: <marco> doesn't make more sense to call it DATABASE_CREATION?
define ('XARINSTALL_PHASE_ADMIN_CREATION',      '6');
define ('XARINSTALL_PHASE_PLUGIN_INSTALL',      '7');
define ('XARINSTALL_PHASE_FINISHED',            '8');

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase the install phase to load
 * @returns bool
 * @return true on success, false on failure
 */
function xarInstallMain($phase = XARINSTALL_PHASE_WELCOME)
{
    xarCoreInit(XARCORE_SYSTEM_NONE); // Does not initialise any optional system

    // Handle installation phase designation
    $phase = (int) xarRequestGetVar('install_phase', 'POST');
    if ($phase == 0) {
        $phase = 1;
    }

    // Make sure we should still be here
    if ($phase >= XARINSTALL_PHASE_ADMIN_CREATION) {
        xarCoreInit(XARCORE_SYSTEM_ALL);
        xarRedirect('index.php?module=installer&type=admin&func=bootstrap');
    }

    // Get module parameters
    list($modName, $modType, $funcName) = xarRequestGetInfo();

    $modName = 'installer';

    $modType = 'admin';

    // Build functioname from phase
    $funcName = 'phase'.$phase;

    // Check for installer theme
    //TODO: use main function as the gateway to the phases and the location for this check
    $installerTheme = xarCore_getSiteVar('BL.DefaultTheme');
    if (strcmp(XARINSTALL_THEME, $installerTheme)) {
        $varDir = xarCoreGetVarDirPath();
        xarCore_die('Please change the BL.DefaultTheme variable in ' .$varDir.'/config.site.xml
        from '.$installerTheme.' to installer');
    }

    // Handle language setting

    // Load installer module
    $res = xarInstallLoad($modName, $modType);
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back
    }

    // if the debugger is active, start it
    if (xarCoreIsDebuggerActive()) {
       ob_start();
    }

    // Run installer function
    $mainModuleOutput = xarInstallFunc($modName, $modType, $funcName);

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

    // Close the session
    //xarSession_close();

    if (xarResponseIsRedirected()) {
        // If the redirection header was yet sent we can't handle exceptions
        // However if we're here with a thrown exception it means that the mod developer
        // is not checking exceptions, so it's also their fault.
        return true;
    }

    // Here we check for exceptions even if $res isn't empty
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back
    }

    // Render page
    $pageOutput = xarTpl_renderPage($mainModuleOutput);

    // Handle exceptions
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    echo $pageOutput;

    return true;
}

if (!isset($phase)) {
    $phase = XARINSTALL_PHASE_WELCOME;
}
$res = xarInstallMain($phase);

if (!isset($res)) {

    // If we're here there must be surely an uncaught exception
    $text = xarML('Caught exception');
    $text .= '<br />';
    $text .= xarExceptionRender('html');

    xarLogException(XARLOG_LEVEL_ERROR);

    // TODO: <marco> Do fallback if raised exception is coming from template engine
    if (xarExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo '<html><head><title>Error</title><body>' . $text . '</body></html>';
    } else {
        // It's important here to free exception before caling xarTpl_renderPage
        xarExceptionFree();
        // Render page
        $pageOutput = xarTpl_renderPage($text);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            // Fallback to raw html
            $msg = '<font color="red">The current page is shown because the Blocklayout Template Engine failed to render the page, however this could be due to a problem not in BL itself but in the template. BL has raised or has left uncaught the following exception:</font>';
            $msg .= '<br /><br />';
            $msg .= xarExceptionRender('html');
            $msg .= '<br />';
            $msg .= '<font color="red">The following exception is instead the exception caught from the main catch clause (Please note that they could be the same if they were raised inside BL or inside the template):</font>';
            $msg .= '<br /><br />';
            $msg .= $text;
            echo "<html><head><title>Error</title><body>$msg</body></html>";
        } else {
            echo $pageOutput;
        }
    }
}

// Kill the debugger
xarCore_disposeDebugger();

?>
