<?php
/**
 * Pre Core - mininmal, lightweight collection of utility functions
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author jsb
*/

/**
 * Initializes the pre core system - if there is ever anything to initialize
 *
 * @access public
 * @return bool true
 */
function xarPreCoreInit()
{
    // Core initialized register the shutdown function
    //register_shutdown_function('xarPreCore__shutdown_handler');
    return true;
}

/**
 * Pre-Core shutdown handler
 *
 * @access private
 */
function xarPreCore__shutdown_handler()
{
    //xarLogMessage("xarCache shutdown handler");
}

/**
 * Returns the path name for the var directory
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author jsb
 * @access public
 * @return string the var directory path name
 */
function xarPreCoreGetVarDirPath()
{
    static $varpath = null;
    if (isset($varpath)) return $varpath;
    if (file_exists('./var/.key.php')) {
        include './var/.key.php';
        $varpath = $protectedVarPath;
    } else {
        $varpath = './var';
    }
    return $varpath;
}

?>
