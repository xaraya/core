<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

include 'includes/xarCore.php';

function xarMain()
{
    // Load the core with all optional systems loaded
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Get module parameters
    list($modName, $modType, $funcName) = xarRequestGetInfo();

    // Adjust BL settings
    // Set the default page title
    xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarConfigGetVar('Site.Core.Slogan'));

    // ANSWER <marco>: Who's gonna use that?
    // EXAMPLE <mikespub>: print, rss, wap, ...
    // Allow theme override in URL first
    $themeName = xarVarCleanFromInput('theme');
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
        xarTplSetThemeName($themeName);
    }

    // Load the module
    $res = xarModLoad($modName, $modType);
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back
    }

    // if the debugger is active, start it
    if (xarCoreIsDebuggerActive()) {
        ob_start();
    }

    // Set the default page title before calling the module function
    xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarConfigGetVar('Site.Core.Slogan'));

    // FIXME: <marco> What's this insanity for?
    // Run the function - also handle cancel button clicking
    if (xarVarCleanFromInput('cancel')) {
        $mainModuleOutput = xarModFunc($modName, $modType);
    } else {
        $mainModuleOutput = xarModFunc($modName, $modType, $funcName);
    }

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
    xarSession_close();

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

    // Set page template
    if ($modType == 'admin' && xarTplGetPageTemplateName() == 'default') {
        // Use the admin.xt page if available when $modType is admin
        xarTplSetPageTemplateName('admin');
    }

    // Note : the page template may be set to something else in the module function
    if (xarTplGetPageTemplateName() == 'default') {
        xarTplSetPageTemplateName($modName);
    }

    // Render page
    //$pageOutput = xarTpl_renderPage($mainModuleOutput, NULL, $template);
    $pageOutput = xarTpl_renderPage($mainModuleOutput);

    // Handle exceptions
    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    echo $pageOutput;

    return true;
}

if (!xarMain()) {

    // If we're here there must be surely an uncaught exception
    if (xarCoreIsDebuggerActive()) {
        $text = xarML('Caught exception');
        $text .= '<br />';
        $text .= xarExceptionRender('html');
    } else {
        $text = xarML('An error occurred while processing your request. The details are:');
        $text .= '<br />';
        $value = xarExceptionValue();
        if (is_object($value) && method_exists($value, 'toHTML')) {
            $text .= '<span style="color: red">'.$value->toHTML().'</span>';
        } else {
            $text .= '<span style="color: purple">'.xarExceptionId().'</span>';
        }
    }

    xarLogException(XARLOG_LEVEL_ERROR);

    // TODO: <marco> Do fallback if raised exception is coming from template engine
    if (xarExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo "<html><head><title>Error</title><body>$text</body></html>";
    } else {
        // It's important here to free exception before caling xarTplPrintPage
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
