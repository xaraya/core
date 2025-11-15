<?php

use Xaraya\Services\xar;

/**
 * Loads the files required for a local services request
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @author Marcel van der Boom
 */

function xarLSLoader($argc, $argv)
{
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
     * Load the Xaraya core
     * @todo: don't load the whole core
     */

    /**
     * Set to the minimalist exception handler
     */
    xarDebug::setExceptionHandler(['ExceptionHandlers','bone']);

    /**
     * We need a (fake) ip address to run Xaraya
     */
    if (!isset($_SERVER['REMOTE_ADDR'])) {
        putenv("REMOTE_ADDR=127.0.0.1");
    }
    try {
        xar::load();
    } catch (Exception $e) {
        print_r($e->getMessage());
        exit;
    }
}

/**
 * Entry point for local services
 *
 * Also known as the command line entry point
 *
 * call sign: php ./ls.php <type> [args]
 *
 * @package core\entrypoints
 * @subpackage entrypoints
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @todo use cli/argument parsing library (perhaps getOpt from PEAR)
 * @todo move this into /bin
 * @todo add site instance parameter
 * @todo centralize user/password entry in here and outside the xarcliapi
 * @author Marcel van der Boom
**/
function xarLocalServicesMain($argc, $argv)
{
    // Main check
    if (!isset($argv[1])) {
        return usage();
    }
    $handler = $argv[1];
    // Get Xaraya Services Class
    $xar = xar::getServicesClass();
    if ($xar->mod()->isAvailable($handler)) {
        return $xar->mod()->apiFunc($handler, 'cli', 'process', ['argc' => $argc, 'argv' => $argv]);
    } else {
        return usage();
    }
}

function usage()
{
    fwrite(STDERR, "Usage for local services entry point:
    php ./" . basename(__FILE__) . " <type> [-u <user>][-p <pass>] [args]

    <type>   : required designator for request type (module name)
    -u <user>: optional username to pass in
    -p <pass>: optional cleartext password to pass in
    [args]   : arguments specific to the supplied <type>
    NOTES:
       - Any module supporting this entry point will have appropriate code in its xarcliapi folder
       - if PHP doesnt have REMOTE_ADDR available, it will assume 127.0.0.1.
         if that is not correct, make sure that PHP can determine your ip address
         (for example by setting REMOTE_ADDR in the environment)
         \n");
    return 1;
}

/**
 * Set up for local services
 */
try {
    xarLSLoader($argc, $argv);
} catch (Exception $e) {
    return usage();
}
/**
 * Process the local request and shut down
 */
return xarLocalServicesMain($argc, $argv);
