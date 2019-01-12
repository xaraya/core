<?php
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

function xarLSLoader()
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
 * Load the bootstrap file for the minimal classes swe need
 */
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
    include 'bootstrap.php';

/**
 * Set up caching
 * Note: this happens first so we can serve cached pages to first-time visitors
 *       without loading the core
 */
    sys::import('xaraya.caching');
    xarCache::init();

/**
 * Load the Xaraya core
 * @todo: don't load the whole core
 */
    sys::import('xaraya.core');

/**
 * Set to the minimalist exception handler
 */
    sys::import('xaraya.exceptions');
    set_exception_handler(array('ExceptionHandlers','bone'));

/**
 * We need a (fake) ip address to run Xaraya
 */
    if(!isset($_SERVER['REMOTE_ADDR'])) putenv("REMOTE_ADDR=127.0.0.1");
    xarCore::xarInit(xarCore::SYSTEM_ALL);
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
    if(!isset($argv[1])) return usage();
    $handler = $argv[1];
    if(xarMod::isAvailable($handler)) {
        return xarMod::apiFunc($handler,'cli','process',array('argc'=>$argc, 'argv'=>$argv));
    } else {
        fwrite(STDERR,"Usage for local services entry point:
        php5 ./".basename(__FILE__)." <type> [-u <user>][-p <pass>] [args]

        <type>   : required designator for request type (module name)
                   Currently Supported:
                   - 'mail'  : a mail message is supplied at stdin
        -u <user>: optional username to pass in
        -p <pass>: optional cleartext password to pass in
        [args]   : arguments specific to the supplied <type>
        NOTES:
           - if PHP doesnt have REMOTE_ADDR available, it will assume 127.0.0.1.
             if that is not correct, make sure that PHP can determine your ip address
             (for example by setting REMOTE_ADDR in the environment)
             \n");
        return 1;
    }
}

/**
 * Set up for local services
 */
xarLSLoader();
/**
 * Process the local request and shut down
 */
exit(xarLocalServicesMain($argc, $argv));
?>