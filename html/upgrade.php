<?php
/**
 * Xaraya Jamaica Upgrade
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <mfl@netspan.ch>
 */

/** Notes for use:
 *  upgrade.php is now an entry function for the upgrade process
 *  The main upgrade functions are now kept in the installer module.
 *     installer_admin_upgrade2 function contains the main database upgrade routines
 *     installer_admin_upgrade3 function contains miscellaneous upgrade routines
 *  Please add any special notes for a special upgrade in admin-upgrade3.xd in installer.
 *  TODO: cleanup and consolidate the upgrade functions in installer
 */
/**
 * Defines for current upgrade phases
 */

// Assemble the things we'll need to create an object that uses the Xaraya core functionality
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
include 'bootstrap.php';


class Upgrader extends Object
{
    const XARUPGRADE_PHASE_WELCOME    = 1;
    const XARUPGRADE_DATABASE         = 2;
    const XARUPGRADE_MISCELLANEOUS    = 3;
    const XARUPGRADE_PHASE_COMPLETE   = 4;

    private static $instance          = null;

    public static $errormessage       = '';

    protected function __construct()
    {
        sys::import('xaraya.caching');
        xarCache::init();
        sys::import('xaraya.core');
        // Only load what we need from core 
        xarCoreInit(xarCore::SYSTEM_ALL);
        
        // Load the current request
        xarController::getRequest();
        
        // Make sure we see any errors
        error_reporting(E_ALL);

       // Let the system know that we are in the process of installing
        xarVarSetCached('Upgrade', 'upgrading',1);

        // Make sure we can render a page
        xarTplSetPageTitle(xarML('Xaraya Upgrade'));
        if(!xarTplSetThemeName('installer'))
            throw new Exception('You need the installer theme if you want to upgrade Xaraya.');

        // Set the default page title before calling the module function
        xarTplSetPageTitle(xarML("Upgrading Xaraya"));
    
        $output = xarModFunc('installer','admin','upgrade');
        $this->renderPage($output);
    }

    private function renderPage($output)
    {
        if (xarCoreIsDebuggerActive()) {
            if (ob_get_length() > 0) {
                $rawOutput = ob_get_contents();
                $output = 'The following lines were printed in raw mode by module, however this
                             should not happen. The module is probably directly calling functions
                             like echo, print, or printf. Please modify the module to exclude direct output.
                             The module is violating Xaraya architecture principles.<br /><br />'.
                             $rawOutput.
                             '<br /><br />This is the real module output:<br /><br />'.
                             $output;
                ob_end_clean();
            }
        }

        // Render page with the output
        $pageOutput = xarTpl_renderPage($output);
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
            self::$errormessage = xarML("The required file '#(1)' was not found.", $checkpath);
            return false;
        }
        $importpath = str_replace('/','.','modules/installer/' . $path);
        $importpath = substr($importpath,0,strlen($importpath)-4);
        sys::import($importpath);
        return true;
    }

}

// Preparations complete. Call the upgrader now
$upgrader = Upgrader::getInstance();    

?>
