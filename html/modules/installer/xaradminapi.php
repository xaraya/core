<?php
/**
 * Modify the system configuration File
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 */

/**
 * Modify the system configuration file
 *
 * @author Johnny Robeson
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

    // fixes instances where passwords contains --> '
    $dbPass = addslashes($dbPass);

    $systemConfigFile = xarCoreGetVarDirPath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));

    //$dbUname = base64_encode($dbUname);
    //$dbPass = base64_encode($dbPass);

    // Get exception error handler setting
    $enablePHPErrorHandler = xarCore_getSystemVar('Exception.EnablePHPErrorHandler');

    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbType';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbHost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbUname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbPass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbName';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$dbPrefix';", $config_php);
    //$config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);
    $config_php = preg_replace('/\[\'Exception.EnablePHPErrorHandler\'\]\s*=\s*(\'|\")(.*)\\1;/', "['Exception.EnablePHPErrorHandler'] = $enablePHPErrorHandler;", $config_php);


    $fp = fopen ($systemConfigFile, 'wb');
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $osDirectory = xarVarPrepForOS($directory);
    $modInitFile = 'modules/'. $osDirectory. '/xarinit.php';

    if (file_exists($modInitFile)) {
        include_once ($modInitFile);
    } else {
        // modules/modulename/xarinit.php not found?!
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $modInitFile doesn't exist."));
                       return;
    }

    // Run the function, check for existence

    $initFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($initFunc)) {
        $res = $initFunc();

        // Handle exceptions
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;

        if ($res == false) {
            // exception
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException(__FILE__.'('.__LINE__.'): core initialization failed!'));
                           return;
        }
    } else {
        // modulename_init() not found?!
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
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

    //All variables are comming thru $args right now.
/*
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
*/
    // {ML_dont_parse 'includes/xarDB.php'}
    include_once 'includes/xarDB.php';

    // Load in Table Maintainance API
    include_once 'includes/xarTableDDL.php';

    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    if (!defined('XAR_ADODB_DIR')) {
        define('XAR_ADODB_DIR','xaradodb');
    }
    include_once XAR_ADODB_DIR . '/adodb.inc.php';
    $ADODB_CACHE_DIR = xarCoreGetVarDirPath() . "/cache/adodb";

    // Check if there is a xar- version of the driver, and use it.
    // Note the driver we load does not affect the database type.
    if (xarDBdriverExists('xar' . $dbType, 'adodb')) {
        $dbDriver = 'xar' . $dbType;
    } else {
        $dbDriver = $dbType;
    }

    // Start connection
    $dbconn = ADONewConnection($dbDriver);
    if ($dbType == 'postgres') {
        // quick hack to enable Postgres DB creation
        $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass, 'template1');
    } else {
        $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass);
    }
    if (!$dbh) {
        $dbpass = '';
        die("Failed to connect to $dbType://$dbUname:$dbPass@$dbHost/, error message: " . $dbconn->ErrorMsg());
    }

    $query = xarDBCreateDatabase($dbName,$dbType);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}


/**
 * CheckForField
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @returns true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckForField($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) ||
        (!isset($table_name))) {
        $msg = xarML('Invalid Parameter Count');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $query = "desc $table_name";
    $result =& $dbconn->Execute($query);

    for(;!$result->EOF;$result->MoveNext()) {
        if ($result[Field] == $field_name) {
            return true;
        }
    }

    return false;
}

/**
 * GetFieldType
 *
 * @access public
 * @param args['field_name']
 * @param args['table_name']
 * @returns field type
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_GetFieldType($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($field_name)) ||
        (!isset($table_name))) {
        $msg = xarML('Invalid Parameter Count');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $dbconn =& xarDBGetConn();

    $query = "desc $table_name";
    $result =& $dbconn->Execute($query);

    for(;!$result->EOF;$result->MoveNext()) {
        if ($result[Field] == $field_name) {
            return ($row[Type]);
        }
    }
    return;
}

/**
 * CheckTableExists
 *
 * @access public
 * @param args['table_name']
 * @returns true if field exists false otherwise
 * @author Sean Finkle, John Cox
 */
function installer_adminapi_CheckTableExists($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($table_name)) {
        $msg = xarML('Invalid Parameter Count');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $dbconn =& xarDBGetConn();
    $result = $dbconn->MetaTables();
    if (in_array($table_name, $result)){
        return true;
    } else {
        return false;
    }
}

?>
