<?php
/**
 * File: $Id$
 *
 * Installer admin API functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage installer
 * @author Johnny Robeson
 */

/**
 * Modify the system configuration file
 *
 * @param string args['dbHost']
 * @param string args['dbName']
 * @param string args['dbUname']
 * @param string args['dbPass']
 * @param string args['prefix']
 * @param string args['dbType']
 * @return bool
 */
function installer_adminapi_modifyconfig($args)
{
    extract($args);

    $systemConfigFile = xarCoreGetVarDirPath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));

    //$dbUname = base64_encode($dbUname);
    //$dbPass = base64_encode($dbPass);

    // Get Logger Options before we write the file in case they are already set
    $logLevel   = xarCore_getSystemVar('Log.LogLevel');
    $loggerName = xarCore_getSystemVar('Log.LoggerName');
    $loggerArgs = xarCore_getSystemVar('Log.LoggerArgs');

    // Get exception error handler setting
    $enablePHPErrorHandler = xarCore_getSystemVar('Exception.EnablePHPErrorHandler');


    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbType';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbHost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbUname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbPass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbName';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$dbPrefix';", $config_php);
    //$config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);
    $config_php = preg_replace('/\[\'Log.LogLevel\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Log.LogLevel'] = '$logLevel';", $config_php);
    $config_php = preg_replace('/\[\'Log.LoggerName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Log.LoggerName'] = '$loggerName';", $config_php);
    $config_php = preg_replace('/\[\'Log.LoggerArgs\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Log.LoggerArgs'] = $loggerArgs;", $config_php);
    $config_php = preg_replace('/\[\'Exception.EnablePHPErrorHandler\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Exception.EnablePHPErrorHandler'] = $enablePHPErrorHandler;", $config_php);


    $fp = fopen ($systemConfigFile, 'w+');
    fwrite ($fp, $config_php);
    fclose ($fp);

    return true;
}

/**
 * Include a module init file and run a function
 *
 * @access public
 * @param args['directory'] the directory to include
 * @param args['initfunc'] init|upgrade|remove
 * @returns bool
 * @raise BAD_PARAM, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function installer_adminapi_initialise($args)
{
    extract($args);
    

    if (empty($directory) || empty($initfunc)) {
        $msg = xarML('Empty modName (#(1)) or name (#(2)).', $directory, $initFunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $osDirectory = xarVarPrepForOS($directory);
    $modInitFile = 'modules/'. $osDirectory. '/xarinit.php';

    if (file_exists($modInitFile)) {
        include_once ($modInitFile);
    } else {
        // modules/modulename/xarinit.php not found?!
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $modInitFile doesn't exist."));
                       return;
    }

    // Run the function, check for existence
    
    $initFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($initFunc)) {
        $res = $initFunc();
   
        // Handle exceptions
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return;
        
        if ($res == false) {
            // exception
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException(__FILE__.'('.__LINE__.'): core initialization failed!'));
                           return;
        }
    } else {
        // modulename_init() not found?!
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $initFunc doesn't exist."));
                       return;
    }
   
    return true;
}

/**
 * Create a database
 *
 * @access public
 * @param args['dbName']
 * @param args['dbType']
 * @returns bool
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function installer_adminapi_createdb($args)
{
    extract($args);

    if (!isset($dbName)) {
        $dbName = xarCore_getSystemVar('DB.Name');
    }

    if (!isset($dbType)) {
        $dbType = xarCore_getSystemVar('DB.Type');
    }

    // Get connection parameters from config.system.php
    $dbHost  = xarCore_getSystemVar('DB.Host');
    $dbUname = xarCore_getSystemVar('DB.UserName');
    $dbPass  = xarCore_getSystemVar('DB.Password');
    $dbType  = xarCore_getSystemVar('DB.Type');
    // Load in Table Maintainance API
    include_once 'includes/xarTableDDL.php';

    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    if (!defined('ADODB_DIR')) {
        define('ADODB_DIR','xaradodb');
    }
   include_once ADODB_DIR . '/adodb.inc.php';

    // Start connection
    $dbconn = ADONewConnection($dbType);
    $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass);
    if (!$dbh) {
        $dbpass = '';
        die("Failed to connect to $dbType://$dbUname:$dbPass@$dbHost/, error message: " . $dbconn->ErrorMsg());
    }

    $query = xarDBCreateDatabase($dbName,$dbType);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}
?>
