<?php

/**
 * Loads the files required for a web request
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.8.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marco Canini
 */

namespace Xaraya\EntryPoint;

use Xaraya\Services\xar;
use xarRequest;
use xarResponse;
use sys;
use Exception;

// show page load time in template
$GLOBALS["Xaraya_PageTime"] = microtime(true);

/**
 * Load the Xaraya bootstrap so we can get started
 */
require_once __DIR__ . '/bootstrap.php';

class WebEntryPoint
{
    protected $xarServices = null;
    /** @var ?xarRequest */
    protected $xarRequest = null;
    /** @var ?xarResponse */
    protected $xarResponse = null;

    protected function getServicesClass()
    {
        if (!isset($this->xarServices)) {
            $this->xarServices = xar::getServicesClass();
        }
        return $this->xarServices;
    }

    public function loader()
    {
        // initialize bootstrap
        sys::init();
        // start autoload
        sys::autoload();

        // add parent directory to include path - @deprecated 2.7.3 left-over from before?
        set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());

        /**
         * Set up caching
         * Note: this happens first so we can serve cached pages to first-time visitors
         *       without loading the core
         */
        // Note: we may already exit here if session-less page caching is enabled
        xar::cache()->init();

        /**
         * Load the Xaraya core (global context)
         */
        $xar = xar::load();

        $this->xarServices = $xar;
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
     * @author Marco Canini
     * @return bool|void
     */
    public function main()
    {
        // Get Xaraya Services Class
        $xar = $this->getServicesClass();

        // Create the object that models this request
        $request = $this->getRequest();

        // Default Page Title
        $this->setPageTitle();

        // Theme Override
        $this->setThemeName();

        // Get a cache key for this page if it's suitable for page caching
        $cacheKey = $xar->cache()->getPageKey();

        $run = 1;
        // Check if the page is cached
        if (!empty($cacheKey) && $xar->cache()->hasPage($cacheKey)) {
            // Output the cached page *or* a 304 Not Modified status
            if ($xar->cache()->sendPage($cacheKey)) {
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
        $xar->log()->notice($message);

        if ($run) {

            // Set page template
            $this->setPageTemplate();

            // if the debugger is active, start it
            if ($xar->isDebuggerActive()) {
                ob_start();
            }

            // Get context of the request if available
            $context = $request->getServerContext()?->getContext();
            /**
             * Un-comment the next line to enable context trace
             * Show it with <xar:template file="context-trace" module="base" /> or in PHP content block
             */
            //$context->enableTrace(true);
            // Use Twig templates with Xaraya - install xaraya/twig package with composer first
            /** un-comment the next line to activate Twig templates */
            //$context['twig'] = true;

            // Process the request
            $xar->log()->notice('Dispatching request: ' . $request->getModule() . "_" . $request->getType() . "_" . $request->getFunction());
            $response = $xar->ctl()->dispatch($request);

            // Retrieve the output to send to the browser
            $xar->log()->notice('Processing request ' . $request->getModule() . "_" . $request->getType() . "_" . $request->getFunction());
            $mainModuleOutput = $response->getOutput();

            if ($xar->isDebuggerActive()) {
                if (ob_get_length() > 0) {
                    $rawOutput = ob_get_contents();
                    $mainModuleOutput = 'The following lines were printed in raw mode by module, however this
                                        should not happen. The module is probably directly calling functions
                                        like echo, print, or printf. Please modify the module to exclude direct output.
                                        The module is violating Xaraya architecture principles.<br /><br />'
                                        . $rawOutput
                                        . '<br /><br />This is the real module output:<br /><br />'
                                        . $mainModuleOutput;
                }
                ob_end_clean();
            }

            // We're all done, one ServerRequest made
            $xar->log()->notice('Notifying listeners of this request');
            $xar->events()->notify('ServerRequest', [], $context);

            // Render page with the output + pass along the current context
            $xar->log()->notice('Creating the page output');
            $pageOutput = $xar->tpl()->renderPage($mainModuleOutput);

            // Set the output of the page in cache
            if (!empty($cacheKey)) {
                // Save the output in cache *before* sending it to the client
                $xar->cache()->setPage($cacheKey, $pageOutput);
            }

            $xar->log()->notice('Rendering the result page');
            echo $pageOutput;
        }

        return true;
    }

    /**
     * Get the object that models this request
     */
    public function getRequest()
    {
        if (isset($this->xarRequest)) {
            return $this->xarRequest;
        }
        // Get Xaraya Services Class
        $xar = xar::getServicesClass();

        // Create the object that models this request
        $request = $xar->req()->getRequest();
        $xar->ctl()->normalizeRequest($request);
        $xar->log()->notice('Retrieved a request: ' . $request->getModule() . "_" . $request->getType() . "_" . $request->getFunction());

        // Set module name in Services Class for templates
        $xar->setModName($request->getModule());

        // Check the Installation
        if (($request->getModule() != 'installer') && ($xar->sysConfig()->getVar('DB.Installation') != 3)) {
            die('Xaraya was not properly installed. The exact error cannot be diagnosed.<br/>Please rerun the installer. If you have important data in your database please make a backup first.');
        }
        $xar->log()->notice('The installation is checked');

        $this->xarRequest = $request;
        return $this->xarRequest;
    }

    /**
     * Set page title based on site slogan (default)
     */
    public function setPageTitle()
    {
        $xar = $this->getServicesClass();
        $siteSlogan = $xar->mod('themes')->getVar('SiteSlogan');
        $xar->tpl()->setPageTitle($xar->prep()->text($siteSlogan));
        $xar->log()->notice('The page title is set: ' . $xar->tpl()->getPageTitle());
    }

    /**
     * Set theme based on URL param, admin theme or user theme
     */
    public function setThemeName()
    {
        $xar = $this->getServicesClass();
        $request = $this->getRequest();
        $xar->var()->find('theme', $themeName, 'str:1:');
        if (!empty($themeName)) {
            $themeName = $xar->prep()->path($themeName);
            if ($xar->theme()->isAvailable($themeName)) {
                $xar->tpl()->setThemeName($themeName);
                $xar->mem()->set('Themes.name', 'CurrentTheme', $themeName);
            }
            // Admin theme
        } elseif ($xar->user()->isLoggedIn() && $request->getType() == 'admin') {
            $themeName = $xar->mod('themes')->getVar('admin_theme');
            if (!empty($themeName) && $xar->theme()->isAvailable($themeName)) {
                $themeName = $xar->prep()->path($themeName);
                $xar->tpl()->setThemeName(strtolower($themeName));
                $xar->mem()->set('Themes.name', 'CurrentTheme', $themeName);
            }
            // User Override (configured in themes admin modifyconfig)
        } elseif ((bool) $xar->mod('themes')->getVar('enable_user_menu')) {
            // users are allowed to set theme in profile, get user setting...
            $themeName = $xar->mod('themes')->getUserVar('default_theme');
            // get the list of permitted themes
            $user_themes = $xar->mod('themes')->getVar('user_themes');
            $user_themes = !empty($user_themes) ? explode(',', $user_themes) : [];

            // check we have a valid theme
            if (!empty($themeName) && $xar->theme()->isAvailable($themeName)
                && !empty($user_themes) && in_array($themeName, $user_themes)) {
                $themeName = $xar->prep()->path($themeName);
                $xar->tpl()->setThemeName(strtolower($themeName));
                $xar->mem()->set('Themes.name', 'CurrentTheme', $themeName);
            }
        }
        $xar->log()->notice('The theme is set: ' . $xar->tpl()->getThemeName());
        return $themeName;
    }

    /**
     * Set page template based on module type or URL param
     */
    public function setPageTemplate()
    {
        $xar = $this->getServicesClass();
        $request = $this->getRequest();
        if ($xar->user()->isLoggedIn() && ($request->getType() == 'admin') && ($xar->tpl()->getPageTemplateName() == 'default')) {
            // Use the admin-$modName.xt page if available when $modType is admin
            // falling back on admin.xt if the former isn't available
            if (!$xar->tpl()->setPageTemplateName('admin-' . $request->getModule())) {
                $xar->tpl()->setPageTemplateName('admin');
            }
        } elseif (!$xar->user()->isLoggedIn() && ($xar->tpl()->getPageTemplateName() == 'default')) {
            // No need to reset anything here
            // Right now we do not allow for e.g. default-roles
        } elseif (($request->getType() != 'admin') && ($xar->tpl()->getPageTemplateName() == 'default')) {

            // Same thing as for admin on user side
            if (!$xar->tpl()->setPageTemplateName($request->getType() . '-' . $request->getModule())) {
                $xar->tpl()->setPageTemplateName($request->getType());
            }
        }

        // User override for the page template
        $xar->var()->find('pageName', $pageName, 'str:1:');
        if (!empty($pageName)) {
            $pageName = $xar->prep()->text($pageName);
            $xar->tpl()->setPageTemplateName($pageName);
        }
        $xar->log()->notice('The page template is set: ' . $xar->tpl()->getPageTemplateName());
    }
}

// The world is not enough...
/**
 * Set up for a web request
 */
$entrypoint = new WebEntryPoint();
try {
    $entrypoint->loader();
} catch (Exception $e) {
    print_r($e->getMessage());
    exit;
}
/**
 * Process the web request
 */
$entrypoint->main();
// All done, the shutdown handlers take care of the rest
