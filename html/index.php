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
 * Load the Xaraya pre core
 */
include_once 'includes/xarPreCore.php';

/**
 * Set up output caching if enabled
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
if (file_exists(xarPreCoreGetVarDirPath() . '/cache/output/cache.touch')) {
    include_once('includes/xarCache.php');
    // Note : we may already exit here if session-less page caching is enabled
    xarCache_init();
}

/**
 * Load the Xaraya core
 */
include 'includes/xarCore.php';

/**
 * Main Xaraya Entry
 *
 * @access public
 * @return bool
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
    xarVarFetch('theme','str:1:',$themeName,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
        if (xarThemeIsAvailable($themeName)){
            xarTplSetThemeName($themeName);
            xarVarSetCached('Themes.name','CurrentTheme', $themeName);
        }
    }

    // Check if page caching is enabled
    $pageCaching = 0;
    if (defined('XARCACHE_PAGE_IS_ENABLED')) {
        $pageCaching = 1;
        $cacheKey = "$modName-$modType-$funcName";
    }

    $run = 1;
    if ($pageCaching == 1 && xarPageIsCached($cacheKey,'page')) {
        // output the cached page *or* a 304 Not Modified status
        if (xarPageGetCached($cacheKey,'page')) {
            // we could return true here, but we'll continue just in case
            // processing changes below someday...
            $run = 0;
        }
    }

    if ($run) {

        // Load the module
        if (!xarModLoad($modName, $modType)) return; // throw back

        // if the debugger is active, start it
        if (xarCoreIsDebuggerActive()) {
            ob_start();
        }

        // Call the main module function
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

        // Note : the page template may be set to something else in the module function
        if (xarTplGetPageTemplateName() == 'default' && $modType != 'admin') {
            // NOTE: we should fallback to the way we were handling this before
            // (ie: use pages/$modName.xt if pages/user-$modName is not found)
            // instead of just switching to the new way without a deprecation period
            // so as to prevent breaking anyone's sites. <rabbitt>
            if (!xarTplSetPageTemplateName('user-'.$modName)) {
                xarTplSetPageTemplateName($modName);
            }
        }

        // Set page template
        if ($modType == 'admin' && xarTplGetPageTemplateName() == 'default' && xarModGetVar('adminpanels', 'dashboard')) {
            // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTplSetPageTemplateName('admin-'.$modName)) {
                xarTplSetPageTemplateName('admin');
            }
        }

        // Here we check for exceptions even if $res isn't empty
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
        if (!empty($pageName)){
            xarTplSetPageTemplateName($pageName);
        }

        // Render page
        //$pageOutput = xarTpl_renderPage($mainModuleOutput, NULL, $template);
        $pageOutput = xarTpl_renderPage($mainModuleOutput);

        // Handle exceptions (the bubble at the top handler
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // we found a non-core error

        if ($pageCaching == 1) {
            // save the output in cache *before* sending it to the client
            xarPageSetCached($cacheKey, 'page', $pageOutput);
        }

        echo $pageOutput;
    }

    return true;
}

if (!xarMain()) {

    // If we're here there must be surely an uncaught exception
    if (xarCoreIsDebuggerActive()) {
        $text = xarErrorRender('template');
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
    if (xarCurrentErrorID() == 'TEMPLATE_NOT_EXIST') {
        echo "<?xml version=\"1.0\"?>\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head><title>Error</title></head><body>$text</body></html>";
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
// All done, the shutdown handlers take care of the rest
?>
