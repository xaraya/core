<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

// INSTALLER THEME
define('PNINSTALL_THEME','installer');


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

// Include pnCore
include 'includes/pnCore.php';
// Include extra functions
include 'modules/installer/pnfunctions.php';

// Install Phases
define ('PNINSTALL_PHASE_WELCOME',             '1');
define ('PNINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('PNINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
/*TODO: rename to PNINSTALL_PHASE_SYSTEM_CHECK unless we want to implement another
phase for php settings check ..magic quotes, register globals, etc..
*/
define ('PNINSTALL_PHASE_PERMISSIONS_CHECK',   '4');
define ('PNINSTALL_PHASE_SETTINGS_COLLECTION', '5');
// FIXME: <marco> doesn't make more sense to call it DATABASE_CREATION?
define ('PNINSTALL_PHASE_ADMIN_CREATION',      '6');
define ('PNINSTALL_PHASE_PLUGIN_INSTALL',      '7');
define ('PNINSTALL_PHASE_FINISHED',            '8');

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase the install phase to load
 * @returns bool
 * @return true on success, false on failure
 */
function pnInstallMain($phase = PNINSTALL_PHASE_WELCOME)
{
    pnCoreInit(PNCORE_SYSTEM_NONE); // Does not initialise any optional system

    // Get module parameters
    list($modName, $modType, $funcName) = pnRequestGetInfo();

    $modName = 'installer';
    $modType = 'admin';

    // Check for installer theme
    //TODO: use main function as the gateway to the phases and the location for this check
    $installerTheme = pnCore_getSiteVar('BL.Theme.Name');
    if (strcmp(PNINSTALL_THEME, $installerTheme)) {
        $varDir = pnCoreGetVarDirPath();
        die('Please change the BL.Theme.Name variable in ' .$varDir.'/config.site.xml
        from '.$installerTheme.' to installer');
    }

    // Handle language setting

    // Load installer module
    $res = pnInstallLoad($modName, $modType);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    // Handle installation phase designation
    $phase = (int) pnRequestGetVar('install_phase', 'POST');
    if ($phase == 0) {
        $phase = 1;
    }

    // Build functioname from phase
    $funcName = 'phase'.$phase;


    // if the debugger is active, start it
    if (pnCoreIsDebuggerActive()) {
       ob_start();
    }

    // Make sure we should still be here
    if ($phase >= PNINSTALL_PHASE_SETTINGS_COLLECTION) {
        pnReponseRedirect('index.php?module=installer&type=admin&func=bootstrap');
    }
    // Run installer function
    $mainModuleOutput = pnInstallFunc($modName, $modType, $funcName);

    if (pnCoreIsDebuggerActive()) {
        if (ob_get_length() > 0) {
            $rawOutput = ob_get_contents();
            $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                 should not happen. The module is probably directly calling functions
                                 like echo, print, or printf. Please modify the module to exclude direct output.
                                 The module is violating PostNuke architecture principles.<br /><br />'.
                                 $rawOutput.
                                 '<br /><br />This is the real module output:<br /><br />'.
                                 $mainModuleOutput;
        }
        ob_end_clean();
    }

    // Close the session
    //pnSession_close();

    if (pnResponseIsRedirected()) {
        // If the redirection header was yet sent we can't handle exceptions
        // However if we're here with a thrown exception it means that the mod developer
        // is not checking exceptions, so it's also their fault.
        return true;
    }

    // Here we check for exceptions even if $res isn't empty
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    // Render page
    $pageOutput = pnTpl_renderPage($mainModuleOutput);

    // Handle exceptions
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    echo $pageOutput;

    return true;
}

if (!isset($phase)) {
    $phase = PNINSTALL_PHASE_WELCOME;
}
$res = pnInstallMain($phase);

if (!isset($res)) {

    // If we're here there must be surely an uncaught exception
    $text = pnML('Caught exception');
    $text .= '<br />';
    $text .= pnExceptionRender('html');

    pnLogException(PNLOG_LEVEL_ERROR);

    // TODO: <marco> Do fallback if raised exception is coming from template engine
    if (pnExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo '<html><head><title>Error</title><body>' . $text . '</body></html>';
    } else {
        // It's important here to free exception before caling pnTpl_renderPage
        pnExceptionFree();
        // Render page
        $pageOutput = pnTpl_renderPage($text);
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            // Fallback to raw html
            $msg = '<font color="red">The current page is shown because the Blocklayout Template Engine failed to render the page, however this could be due to a problem not in BL itself but in the template. BL has raised or has left uncaught the following exception:</font>';
            $msg .= '<br /><br />';
            $msg .= pnExceptionRender('html');
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
pnCore_disposeDebugger();

?>
