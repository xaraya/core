<?php
/**
 * Loads the files required for a web request
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marco Canini
 */
//require dirname(__DIR__).'/vendor/autoload.php';

function xarLoader()
{
    global $systemConfiguration;
    $GLOBALS["Xaraya_PageTime"] = microtime(true); 

/**
 * Load the layout file so we know where to find the Xaraya directories
 */
    if (!isset($systemConfiguration)) {
		$systemConfiguration = array();
		include_once 'var/layout.system.php';
    }
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
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
    if (!class_exists('xarObject')) {
	    include_once 'bootstrap.php';
    }

/**
 * Set up caching
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
    sys::import('xaraya.caching');
    // Note: we may already exit here if session-less page caching is enabled
    xarCache::init();

/**
 * Load the Xaraya core
 */
    sys::import('xaraya.core');
    xarCore::xarInit(xarCore::SYSTEM_ALL);
}

/**
 * Xaraya Web Interface Entry Point 
 *
 * Main Xaraya Entry<br/>
 * This function is called with each page request<br/>
 * It does the following:<br/>
 * 1. Loads the Xaraya core<br/>
 * 2. Sets page title and theme to use in display<br/>
 * 3. Sets the theme's page to use for display (admin, user, default...)<br/>
 * 4. Processes the request<br/>
 * 5. Renders the request output (sends the output to the browser)
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marco Canini
 * @return bool|void
 */
function xarMain()
{
    // Create the object that models this request
    $request = xarController::getRequest();
    xarController::normalizeRequest();
    xarLog::message('Retrieved a request: ' . $request->getModule() . "_" . $request->getType() . "_"  . $request->getFunction(), xarLog::LEVEL_NOTICE);

    // Default Page Title
    $SiteSlogan = xarModVars::get('themes', 'SiteSlogan');
    xarTpl::setPageTitle(xarVar::prepForDisplay($SiteSlogan));
    xarLog::message('The page title is set: ' . xarTpl::getPageTitle(), xarLog::LEVEL_NOTICE);
    
    // Check the Installation
    if (($request->getModule() != 'installer') && (xarSystemVars::get(sys::CONFIG, 'DB.Installation') != 3))
        die('Xaraya was not properly installed. The exact error cannot be diagnosed.<br/>Please rerun the installer. If you have important data in your database please make a backup first.');
    xarLog::message('The installation is checked', xarLog::LEVEL_NOTICE);

    // Theme Override
    xarVar::fetch('theme','str:1:',$themeName,'',xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
    if (!empty($themeName)) {
        $themeName = xarVar::prepForOS($themeName);
        if (xarTheme::isAvailable($themeName)){
            xarTpl::setThemeName($themeName);
            xarVar::setCached('Themes.name','CurrentTheme', $themeName);
        }
    // Admin theme 
    } elseif (xarUser::isLoggedIn() && $request->getType() == 'admin') {
        $themeName = xarModVars::get('themes', 'admin_theme');
        if (!empty($themeName) && xarTheme::isAvailable($themeName)) {
            $themeName = xarVar::prepForOS($themeName);
            xarTpl::setThemeName(strtolower($themeName));
            xarVar::setCached('Themes.name','CurrentTheme', $themeName);
        }            
    // User Override (configured in themes admin modifyconfig)
    } elseif ((bool) xarModVars::get('themes', 'enable_user_menu') == true) {
        // users are allowed to set theme in profile, get user setting...
        $themeName = xarModUserVars::get('themes', 'default_theme');
        // get the list of permitted themes
        $user_themes = xarModVars::get('themes', 'user_themes');
        $user_themes = !empty($user_themes) ? explode(',',$user_themes) : array();

        // check we have a valid theme 
        if (!empty($themeName) && xarTheme::isAvailable($themeName) && 
            !empty($user_themes) && in_array($themeName, $user_themes)) {
            $themeName = xarVar::prepForOS($themeName);
            xarTpl::setThemeName(strtolower($themeName));
            xarVar::setCached('Themes.name','CurrentTheme', $themeName);
        }
    }    
    xarLog::message('The theme is set: ' . xarTpl::getThemeName(), xarLog::LEVEL_NOTICE);

    // Get a cache key for this page if it's suitable for page caching
    $cacheKey = xarCache::getPageKey();

    $run = 1;
    // Check if the page is cached
    if (!empty($cacheKey) && xarPageCache::isCached($cacheKey)) {
        // Output the cached page *or* a 304 Not Modified status
        if (xarPageCache::getCached($cacheKey)) {
            // We could return true here, but we'll continue just in case
            // processing changes below someday...
            $run = 0;
        }
    }
    if ($run) {
        $message = 'The page is not cached. Continue processing the request.';
    } else {
        $message = 'The page is cached. Using the cached page.';
    }
    xarLog::message($message, xarLog::LEVEL_NOTICE);

    if ($run) {

        // Set page template
        if (xarUser::isLoggedIn() && ($request->getType() == 'admin') && (xarTpl::getPageTemplateName() == 'default')) {
            // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!xarTpl::setPageTemplateName('admin-'.$request->getModule())) {
                xarTpl::setPageTemplateName('admin');
            }
        } elseif (!xarUser::isLoggedIn() && (xarTpl::getPageTemplateName() == 'default')) {
            // No need to reset anything here
            // Right now we do not allow for e.g. default-roles
        } elseif (($request->getType() != 'admin') && (xarTpl::getPageTemplateName() == 'default')) {
        
            // Same thing as for admin on user side
            if (!xarTpl::setPageTemplateName($request->getType().'-'.$request->getModule())) {
                xarTpl::setPageTemplateName($request->getType());
            }
        }

        // User override for the page template
        xarVar::fetch('pageName','str:1:', $pageName, '', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        if (!empty($pageName)){
            xarTpl::setPageTemplateName($pageName);
        }
        xarLog::message('The page template is set: ' . xarTpl::getPageTemplateName(), xarLog::LEVEL_NOTICE);

        // if the debugger is active, start it
        if (xarCore::isDebuggerActive()) {
            ob_start();
        }

        // Process the request
        xarLog::message('Dispatching request: ' . $request->getModule() . "_" . $request->getType() . "_"  . $request->getFunction(), xarLog::LEVEL_NOTICE);
        xarController::dispatch($request);

        // Retrieve the output to send to the browser
        xarLog::message('Processing request ' . $request->getModule() . "_"  . $request->getType() . "_"  . $request->getFunction(), xarLog::LEVEL_NOTICE);
        $mainModuleOutput = xarController::$response->getOutput();

        if (xarCore::isDebuggerActive()) {
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
        xarLog::message('Notifying listeners of this request', xarLog::LEVEL_NOTICE);
        xarEvents::notify('ServerRequest');
        
        // Render page with the output
        xarLog::message('Creating the page output', xarLog::LEVEL_NOTICE);
        $pageOutput = xarTpl::renderPage($mainModuleOutput);

        // Set the output of the page in cache
        if (!empty($cacheKey)) {
            // Save the output in cache *before* sending it to the client
            xarPageCache::setCached($cacheKey, $pageOutput);
        }

        xarLog::message('Rendering the result page', xarLog::LEVEL_NOTICE);
        echo $pageOutput;
    }

    return true;
}

// The world is not enough...
/**
 * Set up for a web request
 */
try {
    xarLoader();
} catch (Exception $e) {
    print_r($e->getMessage());
    exit;
}
/**
 * Process the web request
 */
xarMain();
// All done, the shutdown handlers take care of the rest
