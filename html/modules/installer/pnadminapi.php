<?php // $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2002 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Paul Rosania
// Purpose of file: Installer display functions
// ----------------------------------------------------------------------




// TODO: EXCEPTIONS!!
/**
 * Bootstrap Xaraya
 *
 * @param none
 * @returns bool
 */
function installer_adminapi_bootstrap()
{
    unset($GLOBALS['PNSVuid']);
    unset($GLOBALS['PNSVnavigationLocale']);
    pnCoreInit(PNCORE_SYSTEM_USER);
    
    // log in admin user
    $res = pnUserLogIn('admin', 'password', false);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    die('user loggedin');
    // load modules API
    $res = pnModAPILoad('modules', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    pnResponseRedirect(pnModURL('installer', 'admin', 'create_administrator'));
    return array();
}

/**
 * Create default administrator
 *
 * @access public
 * @param none
 * @returns bool
 */
function installer_admin_create_administrator()
{
    if (!pnSecAuthAction(0, 'Installer::', '::', ACCESS_ADMIN)) {
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException(__FILE__."(".__LINE__."): You do not have permission to access the Installer module."));return;
    }

    if (!pnVarCleanFromInput('create')) {
        return array();
    }

    list ($username,
          $name,
          $password,
          $email,
          $url) = pnVarCleanFromInput('install_admin_username',
                                       'install_admin_name',
                                       'install_admin_password',
                                       'install_admin_email',
                                       'install_admin_url');

    $res = pnModAPILoad('users', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    /*
    $res = pnModAPIFunc('users', 'admin', 'update', array('uid'   => 2,
                                                          'name'  => $name,
                                                          'uname' => $username,
                                                          'email' => $email,
                                                          'pass'  => $password,
                                                          'url'   => $url));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    */
    pnRedirect(pnModURL('installer', 'admin', 'finish'));
}

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

    $systemConfigFile = pnCoreGetVarDirPath() . '/config.system.php';
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
        $msg = pnML('Empty modName (#(1)) or name (#(2)).', $directory, $initFunc);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $osDirectory = pnVarPrepForOS($directory);
    $modInitFile = 'modules/'. $osDirectory. '/pninit.php';

    if (file_exists($modInitFile)) {
        include_once ($modInitFile);
    } else {
        // modules/modulename/pninit.php not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $modInitFile doesn't exist."));return;
    }

    // Run the function, check for existence
    $initFunc = $osDirectory.'_'.$initfunc;
    if (function_exists($initFunc)) {
        $res = $initFunc();
        // Handle exceptions
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            return;
        }
        if ($res == false) {
            // exception
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException(__FILE__.'('.__LINE__.'): core initialization failed!'));return;
        }
    } else {
        // modulename_init() not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $initFunc doesn't exist."));return;
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
        $dbName = pnCore_getSystemVar('DB.Name');
    }

    if (!isset($dbType)) {
        $dbType = pnCore_getSystemVar('DB.Type');
    }

    // Get connection parameters from config.system.php
    $dbHost  = pnCore_getSystemVar('DB.Host');
    $dbUname = pnCore_getSystemVar('DB.UserName');
    $dbPass  = pnCore_getSystemVar('DB.Password');
    $dbType  = pnCore_getSystemVar('DB.Type');
    // Load in Table Maintainance API
    include_once 'includes/pnTableDDL.php';

    // Load in ADODB
    define('ADODB_DIR','pnadodb');
    include_once 'pnadodb/adodb.inc.php';

    // Start connection
    $dbconn = ADONewConnection($dbType);
    $dbh = $dbconn->Connect($dbHost, $dbUname, $dbPass);
    if (!$dbh) {
        $dbpass = '';
        die("Failed to connect to $dbType://$dbUname:$dbPass@$dbHost/, error message: " . $dbconn->ErrorMsg());
    }

    $query = pnDBCreateDatabase($dbName,$dbType);

    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
       $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
       pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                      new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
       return NULL;
    }

    return true;
}
?>
