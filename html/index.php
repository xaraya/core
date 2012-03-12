<?php
/**
 * Xaraya Web Interface Entry Point 
 *
 * @package core
 * @subpackage entrypoint
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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

    // Create the object that models this request
    $request = xarController::getRequest();
    xarController::normalizeRequest();

    // Default Page Title
    $SiteSlogan = xarModVars::get('themes', 'SiteSlogan');
    xarTpl::setPageTitle(xarVarPrepForDisplay($SiteSlogan));

    // Theme Override
    xarVarFetch('theme','str:1:',$themeName,'',XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
    if (!empty($themeName)) {
        $themeName = xarVarPrepForOS($themeName);
        if (xarThemeIsAvailable($themeName)){
            xarTpl::setThemeName($themeName);
            xarVarSetCached('Themes.name','CurrentTheme', $themeName);
        }
    // admin theme 
    } elseif (xarUserIsLoggedIn() && $request->getType() == 'admin') {
        $themeName = xarModVars::get('themes', 'admin_theme');
        if (!empty($themeName) && xarThemeIsAvailable($themeName)) {
            $themeName = xarVarPrepForOS($themeName);
            xarTpl::setThemeName(strtolower($themeName));
            xarVarSetCached('Themes.name','CurrentTheme', $themeName);
        }            
    // User Override (configured in themes admin modifyconfig)
    } elseif ((bool) xarModVars::get('themes', 'enable_user_menu') == true) {
        // users are allowed to set theme in profile, get user setting...
        $themeName = xarModUserVars::get('themes', 'default_theme');
        // get the list of permitted themes
        $user_themes = xarModVars::get('themes', 'user_themes');
        $user_themes = !empty($user_themes) ? explode(',',$user_themes) : array();

        // check we have a valid theme 
        if (!empty($themeName) && xarThemeIsAvailable($themeName) && 
            !empty($user_themes) && in_array($themeName, $user_themes)) {
            $themeName = xarVarPrepForOS($themeName);
            xarTpl::setThemeName(strtolower($themeName));
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

        // Process the request
        xarController::dispatch($request);
        // Retrieve the output to send to the browser
        $mainModuleOutput = xarController::$response->getOutput();

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
        //xarEvents::trigger('ServerRequest');
        xarEvents::notify('ServerRequest');
        
        // Set page template
        if (xarUserIsLoggedIn() && $request->getType() == 'admin' && xarTpl::getPageTemplateName() == 'default') {
             // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTpl::setPageTemplateName('admin-'.$request->getModule())) {
                xarTpl::setPageTemplateName('admin');
            }
        } elseif (xarUserIsLoggedIn() && $request->getType() == 'user' && xarTpl::getPageTemplateName() == 'default') {
            // Same thing for user side where user is logged in
            if (!xarTpl::setPageTemplateName('user-'.$request->getModule())) {
                xarTpl::setPageTemplateName('user');
            }
        } elseif ($request->getType() == 'user' && xarTplGetPageTemplateName() == 'default') {
            // For the anonymous user, see if a module specific page exists
            if (!xarTplSetPageTemplateName('user-'.$request->getModule())) {
                xarTplSetPageTemplateName($request->getModule());
            }
        }

        xarVarFetch('pageName','str:1:', $pageName, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY);
        if (!empty($pageName)){
            xarTpl::setPageTemplateName($pageName);
        }

        // Render page with the output
        $pageOutput = xarTpl::renderPage($mainModuleOutput);

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
