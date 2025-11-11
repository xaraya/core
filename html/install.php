<?php

/**
 * Xaraya Installer
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Johnny Robeson
 * @author Paul Rosania
 * @author Marc Lutolf
 * @author Marcel van der Boom
 */

use Xaraya\Context\ContextFactory;
use Xaraya\Services\xar;

/**
 * 0. basic requirements
 * ---check required extensions and PHP version
 * 1. select language
 * ---set language
 * 2. read license agreement
 * ---check agreement state
 * 3. set config.php permissions
 * ---check permissions
 * 4. input database information
 * ---verify, write config.php, install basic dataset (inc. default admin), bootstrap
 * 5. create administrator
 * ---modify administrator information in xar_users
 * 6. pick optional components
 * ---call optional components' init funcs, disable non-reusable areas of install module
 * 7. finished!
 *
 * The load sequence is:
 * --- phase1.php
 * --- phase2.php
 * --- phase3.php
 * --- phase4.php
 * --- phase5.php
 * --- bootstrap.php
 * --- create_administrator.php
 * --- security.php
 * --- cleanup.php
 * --- finish.php
*/

class xarInst
{
    /**
     * Installation constants we work with
     *
    **/
    public const MYSQL_REQUIRED_VERSION               = '5.7.0';
    public const PGSQL_REQUIRED_VERSION               = '8.0.0';
    public const SQLITE_REQUIRED_VERSION              = '3.37.0';
    public const PHP_REQUIRED_VERSION                 = '8.2.0';

}

/**
 * Loads the files required for running an installation
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
function xarInstallLoader()
{
    $xmlextension = extension_loaded('xml');
    $xslextension = extension_loaded('xsl');
    $mbsextension = extension_loaded('mbstring');

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION, xarInst::PHP_REQUIRED_VERSION, '>=')) {
            $metRequiredPHPVersion = true;
        }
    } else {
        $metRequiredPHPVersion = false;
    }
    if (!$metRequiredPHPVersion || !$xmlextension || !$xslextension || !$mbsextension) {
        header('Location: requirements.html');
        exit;
    }

    /**
     * Load the bootstrap file for the minimal classes we need
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


    // Besides what we explicitly load, we dont want to load
    // anything extra for maximum control
    // Todo: rework the stuff below to use xarCore::xarInit()
    $whatToLoad = xarConst::SYSTEM_NONE;

    // Start Exception Handling System very early

    // Enable debugging always for the installer
    xarCore::activateDebugger(xarConst::DBG_ACTIVE | xarConst::DBG_EXCEPTIONS | xarConst::DBG_SHOW_PARAMS_IN_BT, $xar);

    // Include some extra functions, as the installer is somewhat special
    // for loading gui and api functions

    // Basic systems always loaded

    // Start Logging Facilities as soon as possible
    $systemArgs = [];
    $xar->log()->init($systemArgs);

    /*
        As long as we are coming in through install.php we need to pick up the
        bones if something goes wrong, so set the handler to bone for now
    */
    xarDebug::setExceptionHandler(['ExceptionHandlers','bone']);

    // Start HTTP Protocol Server/Request/Response utilities
    $systemArgs = [
        'enableShortURLsSupport' => false,
        'defaultModuleName'      => 'installer',
        'defaultModuleType'      => 'admin',
        'defaultModuleFunction'  => 'main',
        'generateXMLURLs'        => false,
    ];
    $xar->req()->init($systemArgs);
    $xar->ctl()->init($systemArgs);

    // Start BlockLayout Template Engine
    // This is probably the trickiest part, but we want the installer
    // templateable too obviously
    $systemArgs = [
        'enableTemplatesCaching' => false,
        'defaultThemeDir'        => 'installer',
        'pageTemplateName'       => 'admin',
        'defaultDocType'         => 'xhtml1-strict',
        'generateXMLURLs'        => false,
    ];
    $xar->tpl()->init($systemArgs);

    // Get the install language everytime we request install.php
    // We need the var to be able to initialize MLS, but we need MLS to get the var
    // So we need something temporarily set, so we can continue
    // We set a utf locale intially, otherwise the combo box wont be filled correctly
    // for language names which include utf characters
    $xar->mls()->setMode('SINGLE');
    $xar->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');

    // Construct an array of the available locale folders
    $locale_dir = sys::varpath() . '/locales/';
    $allowedLocales = [];
    if (is_dir($locale_dir)) {
        if ($dh = opendir($locale_dir)) {
            while (($file = readdir($dh)) !== false) {
                // Exclude the current, previous and the Bitkeeper folder
                // (just for us to be able to test, wont affect users who use a build)
                if ($file == '.' || $file == '..' || filetype($locale_dir . $file) == 'file') {
                    continue;
                }
                if (filetype(realpath($locale_dir . $file)) == 'dir'
                   && file_exists(realpath($locale_dir . $file . '/locale.xml'))) {
                    $allowedLocales[] = $file;
                }
            }
            closedir($dh);
        }
    }

    if (empty($allowedLocales)) {
        throw new Exception("The var directory is corrupted: no locale was found!");
    }
    // A sorted combobox is better
    sort($allowedLocales);

    // Start Multi Language System
    $systemArgs = [
        'translationsBackend' => 'xml2php',
        'MLSMode'             => 'BOXED',
        'defaultLocale'       => $install_language,
        'allowedLocales'      => $allowedLocales,
    ];
    $xar->mls()->init($systemArgs);
}

/**
 * Entry function for the installer
 *
 * @access private
 * @param integer phase the install phase to load
 * @return bool true on success, false on failure
 * @todo <johnny> use non caching templates until they are set to yes
 */
function xarInstallMain()
{
    // Get Xaraya Services Class
    $xar = xar::getServicesClass();

    // Let the system know that we are in the process of installing
    $xar->mem()->set('installer', 'installing', 1);
    // Set module name in Services Class for templates
    $xar->setModName('installer');

    // Make sure we can render a page
    $xar->tpl()->setPageTitle($xar->mls()->translate('Xaraya installer'));
    if (!$xar->tpl()->setThemeName('installer')) {
        throw new Exception('You need the installer theme if you want to install Xaraya.');
    }

    // Handle installation phase designation
    $xar->var()->find('install_phase', $phase, 'int:1:6', 1);

    // Build function name from phase
    $funcName = 'phase' . $phase;

    // If the debugger is active, start it
    if ($xar->isDebuggerActive()) {
        ob_start();
    }

    // Set the default page title before calling the module function
    $xar->tpl()->setPageTitle($xar->mls()->translate("Installing Xaraya"));

    // Run installer function
    $mainModuleOutput = xarInstall::func($funcName);

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

    // Render page using the installer theme admin.xt page template
    $pageOutput = $xar->tpl()->renderPage($mainModuleOutput, 'admin');

    echo $pageOutput;
    return true;
}

/**
 * Set up for the installer
 */
xarInstallLoader();
/**
 * Run  the installer
 */
xarInstallMain();
