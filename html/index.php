<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------

include 'includes/pnCore.php';

function pnMain()
{
    // Load the core with all optional systems loaded
    pnCoreInit(PNCORE_SYSTEM_ALL);
    
    // Get module parameters
    list($modName, $modType, $funcName) = pnRequestGetInfo();

    // Load the module
    $res = pnModLoad($modName, $modType);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    // if the debugger is active, start it
    if (pnCoreIsDebuggerActive()) {
        ob_start();
    }

    // FIXME: <marco> What's this insanity for?
    // Run the function - also handle cancel button clicking
    if (pnVarCleanFromInput('cancel')) {
        $mainModuleOutput = pnModFunc($modName, $modType);
    } else {
        $mainModuleOutput = pnModFunc($modName, $modType, $funcName);
    }

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
    pnSession_close();

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

// TODO: formalize this and put code in pnTpl_renderPage ?
    // Override default page template
    if (pnVarIsCached('PageSettings','template')) {
        $template = pnVarGetCached('PageSettings','template');
    } else {
        $template = 'default';
    }
    // Override default page title
    if (pnVarIsCached('PageSettings','title')) {
       $title = pnVarGetCached('PageSettings','title');
       // dirty trick :-)
       pnVarSetCached('Config.Variables', 'SiteSlogan', $title);
    }

    // Render page
    $pageOutput = pnTpl_renderPage($mainModuleOutput, NULL, $template);
//    $pageOutput = pnTpl_renderPage($mainModuleOutput);

    // Handle exceptions
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    echo $pageOutput;

    return true;
}

$res = pnMain();
if (!isset($res)) {

    // If we're here there must be surely an uncaught exception
    $text = pnML('Caught exception');
    $text .= '<br />';
    $text .= pnExceptionRender('html');

    pnLogException(PNLOG_LEVEL_ERROR);

    // TODO: <marco> Do fallback if raised exception is coming from template engine
    if (pnExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo "<html><head><title>Error</title><body>$text</body></html>";
    } else {
        // It's important here to free exception before caling pnTplPrintPage
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
