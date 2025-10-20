<?php

/**
 * Loads the files required for running an upgrade
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
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
     * Set up caching
     */
    sys::import('xaraya.caching');
    xarCache::init();

    /**
     * Load the Xaraya core
     */
    sys::import('xaraya.core');
    xarCore::xarInit(xarCore::SYSTEM_ALL);
}

/**
 * Xaraya Upgrade Entry Point
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
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
class Upgrader
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
        //xarConfigVars::set(null, 'System.Core.VersionNum', '2.4.0');
        // Let the system know that we are in the process of installing
        xarVar::setCached('Upgrade', 'upgrading', 1);

        // Load the current request
        xarController::getRequest();

        // Make sure we see any errors
        error_reporting(E_ALL);

        // Make sure we can render a page
        xarTpl::setPageTitle(xarMLS::translate('Xaraya Upgrade'));
        if (!xarTpl::setThemeName('installer')) {
            throw new Exception('You need the installer theme if you want to upgrade Xaraya.');
        }

        // Set the default page title before calling the module function
        xarTpl::setPageTitle(xarMLS::translate("Upgrading Xaraya"));

        $output = xarMod::guiFunc('installer', 'admin', 'upgrade');
        $this->renderPage($output);
    }

    private function renderPage($output)
    {
        if (xarCore::isDebuggerActive()) {
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
        $pageOutput = xarTpl::renderPage($output);
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
            self::$errormessage = xarMLS::translate("The required file '#(1)' was not found.", $checkpath);
            return false;
        }
        $importpath = str_replace('/', '.', 'modules/installer/' . $path);
        $importpath = substr($importpath, 0, strlen($importpath) - 4);
        sys::import($importpath);
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
Upgrader::getInstance();
