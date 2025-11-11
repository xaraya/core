<?php

use Xaraya\Context\ContextFactory;
use Xaraya\Services\xar;

/**
 * Loads the files required for running an upgrade
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marc Lutolf <mfl@netspan.ch>
 */
function xarUpgradeLoader()
{
    /**
     * Load the Xaraya bootstrap so we can get started
     */
    require_once __DIR__ . '/bootstrap.php';

    // initialize bootstrap
    sys::init();
    // start autoload
    sys::autoload();

    // add parent directory to include path - @deprecated 2.7.3 left-over from before?
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());

    /**
     * Get context from globals if not specified (default)
     */
    $context = ContextFactory::fromGlobals(__METHOD__);
    // Set context for core services here first + return static services class
    $xar = xar::setServicesContext($context);

    /**
     * Set up caching
     */
    $xar->cache()->init();

    /**
     * Load the Xaraya core with context
     */
    $xar->load();
}

/**
 * Xaraya Upgrade Entry Point
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marc Lutolf <mfl@netspan.ch>
 */
/** Notes for use:<br/>
 *  upgrade.php is now an entry function for the upgrade process<br/>
 *  The main upgrade functions are now kept in the installer module.<br/>
 *     installer_admin_upgrade2 function contains the main database upgrade routines<br/>
 *     installer_admin_upgrade3 function contains miscellaneous upgrade routines<br/>
 *  Please add any special notes for a special upgrade in admin-upgrade3.xd in installer.<br/>
 *  TODO: cleanup and consolidate the upgrade functions in installer
 */
class xarUpgrader
{
    /**
     * Constants for current upgrade phases
     */
    public const XARUPGRADE_PHASE_WELCOME    = 1;
    public const XARUPGRADE_DATABASE         = 2;
    public const XARUPGRADE_MISCELLANEOUS    = 3;
    public const XARUPGRADE_PHASE_COMPLETE   = 4;

    private static $instance          = null;

    public static $errormessage       = '';

    protected function __construct()
    {
        // Get Xaraya Services Class
        $xar = xar::getServicesClass();

        //$xar->config()->setVar('System.Core.VersionNum', '2.4.1');
        // Let the system know that we are in the process of installing
        $xar->mem()->set('Upgrade', 'upgrading', 1);

        // Set module name in Services Class for templates
        $xar->setModName('installer');

        // Load the current request
        $xar->req()->getRequest();

        // Make sure we see any errors
        error_reporting(E_ALL);

        // Make sure we can render a page
        $xar->tpl()->setPageTitle($xar->mls()->translate('Xaraya Upgrade'));
        if (!$xar->tpl()->setThemeName('installer')) {
            throw new Exception('You need the installer theme if you want to upgrade Xaraya.');
        }

        // Set the default page title before calling the module function
        $xar->tpl()->setPageTitle($xar->mls()->translate("Upgrading Xaraya"));

        $output = $xar->mod()->guiFunc('installer', 'admin', 'upgrade');
        $this->renderPage($output, $xar);
    }

    private function renderPage($output, $xar)
    {
        if ($xar->isDebuggerActive()) {
            if (ob_get_length() > 0) {
                $rawOutput = ob_get_contents();
                $output = 'The following lines were printed in raw mode by module, however this
                             should not happen. The module is probably directly calling functions
                             like echo, print, or printf. Please modify the module to exclude direct output.
                             The module is violating Xaraya architecture principles.<br /><br />'
                             . $rawOutput
                             . '<br /><br />This is the real module output:<br /><br />'
                             . $output;
                ob_end_clean();
            }
        }

        // Render page with the output
        $pageOutput = $xar->tpl()->renderPage($output);
        echo $pageOutput;
        return true;
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function loadFile($path)
    {
        $checkpath = sys::code() . 'modules/installer/' . $path;
        if (!file_exists($checkpath)) {
            self::$errormessage = xar::mls()->translate("The required file '#(1)' was not found.", $checkpath);
            return false;
        }
        // no sys::import here
        include_once $checkpath;
        return true;
    }
}

/**
 * Set up for an upgrade
 */
xarUpgradeLoader();
/**
 * Run the upgrade
 */
xarUpgrader::getInstance();
