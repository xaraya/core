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
    xarVar::fetch('v', 'str:1', $v);
    xarVar::fetch('u', 'str:1', $u);

    /**
     * Get the user information
     */
    $user = xarMod::apiFunc('roles', 'user', 'get', ['id' => $u]);

    /**
     * Redirect to the validation page
     */
    xarController::redirect(xarController::URL(
        'roles',
        'user',
        'getvalidation',
        ['stage'   => 'getvalidate',
            'valcode' => $v,
            'uname'   => $user['uname'],
            'phase'   => 'getvalidate']
    ));
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
