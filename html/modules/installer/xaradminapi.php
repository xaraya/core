<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file: Installer display functions
// ----------------------------------------------------------------------




// TODO: EXCEPTIONS!!


/**
 * Modify the system configuration file
 *
 * @param args['dbHost']
 * @param args['dbName']
 * @param args['dbUname']
 * @param args['dbPass']
 * @param args['prefix']
 * @param args['dbType']
 * @returns bool
 * @return
 */
function installer_adminapi_modifyconfig($args)
{
    extract($args);

    $systemConfigFile = xarCoreGetVarDirPath() . '/config.system.php';
    $config_php = join('', file($systemConfigFile));
    if (isset($HTTP_ENV_VARS['OS']) && strstr($HTTP_ENV_VARS['OS'], 'Win')) {
        $system = 1;
    } else {
        $system = 0;
    }

    //$dbUname = base64_encode($dbUname);
    //$dbPass = base64_encode($dbPass);

    $config_php = preg_replace('/\[\'DB.Type\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Type'] = '$dbType';", $config_php);
    $config_php = preg_replace('/\[\'DB.Host\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Host'] = '$dbHost';", $config_php);
    $config_php = preg_replace('/\[\'DB.UserName\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.UserName'] = '$dbUname';", $config_php);
    $config_php = preg_replace('/\[\'DB.Password\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Password'] = '$dbPass';", $config_php);
    $config_php = preg_replace('/\[\'DB.Name\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Name'] = '$dbName';", $config_php);
    $config_php = preg_replace('/\[\'DB.TablePrefix\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.TablePrefix'] = '$dbPrefix';", $config_php);
    // $config_php = preg_replace('/\[\'system\'\]\s*=\s*(\'|\")(.*)\\1;/', "['system'] = '$system';", $config_php);
    // $config_php = preg_replace('/\[\'DB.Encoded\'\]\s*=\s*(\'|\")(.*)\\1;/', "['DB.Encoded'] = '1';", $config_php);

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
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return;
        }
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
    define('ADODB_DIR','xaradodb');
    include_once 'xaradodb/adodb.inc.php';

    // Start connection
    $dbconn = ADONewConnection($dbType);
    $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass);
    if (!$dbh) {
        $dbpass = '';
        die("Failed to connect to $dbType://$dbUname:$dbPass@$dbHost/, error message: " . $dbconn->ErrorMsg());
    }

    $query = xarDBCreateDatabase($dbName,$dbType);

    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
       $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
       xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                      new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
       return NULL;
    }

    return true;
}
?>