<?php
/**
 * File: $Id: s.install.php 1.156 03/05/11 21:03:58+02:00 marcel@hsdev.com $
 *
 * Xaraya Installer
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Installer
 * @author Johnny Robeson Paul Rosania
 */

/**
 * 1. select language
 * ---set language
 * 2. read license agreement
 * ---check agreement state
 * 3. set config.php permissions
 * ---check permissions
 * 4. input database information
 * ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
 * 5. create administrator
 * ---modify administrator information in xar_users
 * 6. pick optional components
 * ---call optional components' init funcs, disable non-reusable areas of install module
 * 7. finished!
*/

include 'includes/xarCore.php';
// Include extra functions
include 'modules/installer/xarfunctions.php';

//Comment this line to disable debugging
xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);
//xarCoreActivateDebugger(0);

// Basic systems alway loaded
// {ML_dont_parse 'includes/xarLog.php'}
include_once 'includes/xarLog.php';
// {ML_dont_parse 'includes/xarEvt.php'}
include_once 'includes/xarEvt.php';

include_once 'includes/xarException.php';
// {ML_dont_parse 'includes/xarVar.php'}
include_once 'includes/xarVar.php';
// {ML_dont_parse 'includes/xarServer.php'}
include_once 'includes/xarServer.php';
// {ML_dont_parse 'includes/xarMLS.php'}
include_once 'includes/xarMLS.php';
// {ML_dont_parse 'includes/xarTemplate.php'}
include_once 'includes/xarTemplate.php';

$whatToLoad = XARCORE_SYSTEM_NONE;

// Start Logging Facilities
$systemArgs = array('loggerName' => xarCore_getSystemVar('Log.LoggerName'),
                    'loggerArgs' => xarCore_getSystemVar('Log.LoggerArgs'),
                    'level'      => xarCore_getSystemVar('Log.LogLevel'));

xarLog_init($systemArgs, $whatToLoad);

// Start Exception Handling System
$systemArgs = array('enablePHPErrorHandler' => xarCore_getSystemVar('Exception.EnablePHPErrorHandler'));

xarError_init($systemArgs, $whatToLoad);

// Start Event Messaging System
$systemArgs = array('loadLevel' => $whatToLoad);
xarEvt_init($systemArgs, $whatToLoad);

// Start HTTP Protocol Server/Request/Response utilities
$systemArgs = array('enableShortURLsSupport' =>false,
                    'defaultModuleName' => 'installer',
                    'defaultModuleType' => 'admin',
                    'defaultModuleFunction' => 'main',
                    'generateXMLURLs' => false);
xarSerReqRes_init($systemArgs, $whatToLoad);

// Start BlockLayout Template Engine
$systemArgs = array('enableTemplatesCaching' => false,
                    'themesBaseDirectory' => 'themes',
                    'defaultThemeDir' => 'installer');
xarTpl_init($systemArgs, $whatToLoad);

// Start Multi Language System
$systemArgs = array('translationsBackend' => 'php',
                    'MLSMode' => 'SINGLE',
                    'defaultLocale' => 'en_US.iso-8859-1',
                    'allowedLocales' => 'en_US.iso-8859-1');
xarMLS_init($systemArgs, $whatToLoad);

// Install Phases
define ('XARINSTALL_PHASE_WELCOME',             '1');
define ('XARINSTALL_PHASE_LANGUAGE_SELECT',     '2');
define ('XARINSTALL_PHASE_LICENSE_AGREEMENT',   '3');
define ('XARINSTALL_PHASE_SYSTEM_CHECK',        '4');
define ('XARINSTALL_PHASE_SETTINGS_COLLECTION', '5');
define ('XARINSTALL_PHASE_BOOTSTRAP',           '6');

/**
 * Entry function for the installer
 *
 * @access private
 * @param phase integer the install phase to load
 * @return bool true on success, false on failure
 * @todo <johnny> use non caching templates until they are set to yes
 */
function xarInstallMain($phase = XARINSTALL_PHASE_WELCOME)
{

    // let the system know that we are in the process of installing
    xarVarSetCached('installer','installing',1);

    // Make sure we can render a page
    xarTplSetPageTitle('Xaraya installer');
    if (!xarTplSetThemeName('installer')) {
        xarCore_die('You need the installer theme if you want to install Xaraya.');
    }

    // Handle installation phase designation
    $phase = (int) xarRequestGetVar('install_phase', 'POST');

    // if we can't find the phase in the POST variables, check
    // the GET cuz we might have been redirected
    if (!$phase) {
        $phase = (int) xarRequestGetVar('install_phase', 'GET');
    }


    if ($phase == 0) {
        $phase = 1;
    }

    // Make sure we should still be here
    if ($phase >= XARINSTALL_PHASE_BOOTSTRAP) {
        xarResponseRedirect('index.php?module=installer&type=admin&func=bootstrap');
    }

    // Hardcode module name and type
    $modName = 'installer';
    $modType = 'admin';

    // Build function name from phase
    $funcName = 'phase' . $phase;
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

    // Set the default page title before calling the module function
    xarTplSetPageTitle("Installing Xaraya");

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

if (!xarInstallMain($phase)) {

    // If we're here there must be surely an uncaught exception
    $text = xarExceptionRender('template');

    // TODO: #2
    if (xarExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo "<?xml version=\"1.0\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head><title>Error</title><body>$text</body></html>";
    } else {
        // It's important here to free exception before calling xarTplPrintPage
        // As we are in the exception handling phase, we can clear it without side effects.
        xarExceptionFree();
        // Render page
        $pageOutput = xarTpl_renderPage($text);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            // Fallback to raw html
            $msg = '<span style="color: #FF0000;">The current page is shown because the Blocklayout Template Engine failed to render the page, however this could be due to a problem not in BL itself but in the template. BL has raised or has left uncaught the following exception:</span>';
            $msg .= '<br /><br />';
            $msg .= xarExceptionRender('rawhtml');
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

// Kill the debugger
xarCore_disposeDebugger();

?>
