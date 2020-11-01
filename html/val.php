<?php
/**
 * Loads the files required for a validation request
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
*/
function xarValidationLoader()
{
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
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
    include 'bootstrap.php';

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
 * Entry point for validating users
 *
 * @package core\entrypoints
 * @subpackage entrypoint
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author John Cox
 *
 */
function xarValidationMain()
{
/**
 * Get the user ID and the validation code
 */
    if (!xarVar::fetch('v', 'str:1', $v)) return;
    if (!xarVar::fetch('u', 'str:1', $u)) return;

/**
 * Get the user information
 */
    $user = xarMod::apiFunc('roles','user','get', array('id' => $u));

/**
 * Redirect to the validation page
 */
    xarController::redirect(xarController::URL('roles', 'user','getvalidation',
                                  array('stage'   => 'getvalidate',
                                        'valcode' => $v,
                                        'uname'   => $user['uname'],
                                        'phase'   => 'getvalidate')));
    return true;
}

/**
 * Set up for an upgrade
 */
/**
 * Set up for a validation
 */
xarValidationLoader();
/**
 * Run the validation
 */
xarValidationMain();    
