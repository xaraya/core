<?php
/**
 * Xaraya Web Interface Entry Point
 *
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage entrypoint
 * @author Marco Canini
 */

$GLOBALS["Xaraya_PageTime"] = microtime(true);

/**
 * Load the layout file so we know where to find the Xaraya directories
 */
$systemConfiguration = array();
include 'var/layout.system.php';
if (!isset($systemConfiguration['rootDir'])) $systemConfiguration['rootDir'] = '../';
if (!isset($systemConfiguration['libDir'])) $systemConfiguration['libDir'] = 'lib/';
if (!isset($systemConfiguration['webDir'])) $systemConfiguration['webDir'] = 'html/';
if (!isset($systemConfiguration['codeDir'])) $systemConfiguration['codeDir'] = 'code/';
$GLOBALS['systemConfiguration'] = $systemConfiguration;
if (!empty($systemConfiguration['rootDir'])) {
    set_include_path($systemConfiguration['rootDir'] . PATH_SEPARATOR . get_include_path());
}

/**
 * Load the Xaraya bootstrap so we can get started
 */
include 'bootstrap.php';

/**
 * Set up caching
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
sys::import('xaraya.caching');
// Note : we may already exit here if session-less page caching is enabled
xarCache::init();

/**
 * Load the Xaraya core
 */
sys::import('xaraya.core');

/**
 * Main Xaraya Entry
 *
 * @access public
 * @return bool
 */
function xarMain()
{
    // Load the core with all optional systems loaded
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // Get module parameters
    list($modName, $modType, $funcName) = xarRequest::getInfo();

    // Default Page Title
    $SiteSlogan = xarModVars::get('themes', 'SiteSlogan');
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

    // Get a cache key for this page if it's suitable for page caching
    $cacheKey = xarCache::getPageKey();

    $run = 1;
    // Check if the page is cached
    if (!empty($cacheKey) && xarPageCache::isCached($cacheKey)) {
        // Output the cached page *or* a 304 Not Modified status
        if (xarPageCache::getCached($cacheKey)) {
            // we could return true here, but we'll continue just in case
            // processing changes below someday...
            $run = 0;
        }
    }

    if ($run) {

        // if the debugger is active, start it
        if (xarCoreIsDebuggerActive()) {
            ob_start();
        }

        if (xarRequest::isObjectURL()) {
            sys::import('xaraya.objects');

            // Call the object handler and return the output (or exit with 404 Not Found)
            $mainModuleOutput = xarObject::guiMethod($modType, $funcName);

        } else {

            // Call the main module function and return the output (or exit with 404 Not Found)
            $mainModuleOutput = xarMod::guiFunc($modName, $modType, $funcName);
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

        // We're all done, one ServerRequest made
        xarEvents::trigger('ServerRequest');

        // Set page template
        if ($modType == 'admin' && xarTplGetPageTemplateName() == 'default') {
             // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTplSetPageTemplateName('admin-'.$modName)) {
                xarTplSetPageTemplateName('admin');
            }
        }

        xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
        if (!empty($pageName)){
            xarTplSetPageTemplateName($pageName);
        }

        // Render page with the output
        $pageOutput = xarTpl_renderPage($mainModuleOutput);

        // Set the output of the page in cache
        if (!empty($cacheKey)) {
            // save the output in cache *before* sending it to the client
            xarPageCache::setCached($cacheKey, $pageOutput);
        }

        echo $pageOutput;
    }

    return true;
}

// The world is not enough...
xarMain();
// All done, the shutdown handlers take care of the rest
?>