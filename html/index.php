<?php
/**
 * File: $Id$
 *
 * Xaraya Web Interface Entry Point
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Web Interface Entry Point
 * @author Marco Canini
 */

/**
 * Index Function
*/
include 'includes/xarCore.php';

/**
 * Main Xaraya Entry
 *
 * @access public
 * @return bool
 * @todo <marco> <mikespub> #1 decide whether to accept index.php?theme=$theme URL for rss, print, wap themes, etc..
 * @todo <marco> #2 Do fallback if raised exception is coming from template engine
 */
function xarMain()
{
    // Load the core with all optional systems loaded
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Get module parameters
    list($modName, $modType, $funcName) = xarRequestGetInfo();

    // Default Page Title
    $SiteSlogan = xarModGetVar('themes', 'SiteSlogan');
    xarTplSetPageTitle(xarVarPrepForDisplay($SiteSlogan));

    // Theme Override
    xarVarFetch('theme','str:1:',$themeName,'',XARVAR_NOT_REQUIRED);
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
        xarTplSetThemeName($themeName);
        xarVarSetCached('Themes.name','CurrentTheme', $themeName);
    }

    $caching = 0;

    // Set up caching if enabled
    if (file_exists('var/cache/output/cache.touch')) {
        $caching = 1;
        include 'includes/xarCache.php';
        if (xarCache_init(array('cacheDir' => 'var/cache/output')) == false) {
            $caching = 0;
        }
        $cacheKey = "$modName-$modType-$funcName";
    }

    if ($caching == 1 && xarPageIsCached($cacheKey,'page')) {
        // output the cached page *or* a 304 Not Modified status
        xarPageGetCached($cacheKey,'page');

    } else {

        // Load the module
        if (!xarModLoad($modName, $modType)) return; // throw back

        // if the debugger is active, start it
        if (xarCoreIsDebuggerActive()) {
            ob_start();
        }

        $mainModuleOutput = xarModFunc($modName, $modType, $funcName);


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

        // We're all done, one ServerRequest made
        xarEvt_trigger('ServerRequest');

        if (xarResponseIsRedirected()) return true;
        // Here we check for exceptions even if $res isn't empty
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        // Note : the page template may be set to something else in the module function
        if (xarTplGetPageTemplateName() == 'default') {
            xarTplSetPageTemplateName($modName);
        }

        // Set page template
        if ($modType == 'admin' && xarTplGetPageTemplateName() == 'default') {
            // Use the admin.xt page if available when $modType is admin
            xarTplSetPageTemplateName('admin');
        }

        // Render page
        //$pageOutput = xarTpl_renderPage($mainModuleOutput, NULL, $template);
        $pageOutput = xarTpl_renderPage($mainModuleOutput);

        // Handle exceptions (the bubble at the top handler
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        if ($caching == 1) {
            // save the output in cache *before* sending it to the client
            xarPageSetCached($cacheKey,'page',$pageOutput);
        }

        echo $pageOutput;
    }

    return true;
}

if (!xarMain()) {

    // If we're here there must be surely an uncaught exception
    if (xarCoreIsDebuggerActive()) {
        $text = xarErrorRender('html');
    } else {
        $text = xarML('An error occurred while processing your request. The details are:');
        $text .= '<br />';
        $value = xarCurrentError();
        if (is_object($value) && method_exists($value, 'toHTML')) {
            $text .= '<span style="color: #FF0000;">'.$value->toHTML().'</span>';
        } else {
            $text .= '<span style="color: #999900;">'.xarCurrentErrorID().'</span>';
        }
    }

//    xarLogException(XARLOG_LEVEL_ERROR);

    // TODO: #2
    if (xarExceptionId() == 'TEMPLATE_NOT_EXIST') {
        echo "<?xml version=\"1.0\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head><title>Error</title><body>$text</body></html>";
    } else {
        // It's important here to free exception before calling xarTplPrintPage
        // As we are in the exception handling phase, we can clear it without side effects.
        xarErrorFree();
        // Render page
        $pageOutput = xarTpl_renderPage($text);
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
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

// Close the session
xarSession_close();

// Kill the debugger
xarCore_disposeDebugger();

?>
