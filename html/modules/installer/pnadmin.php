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

//TODO: make this phpdoc true, right now phase 1 is the entry
/**
 * Entry function for the installer module
 *
 * @access public
 * @param none
 * @returns array
 * @return an array of template values
 */
function installer_admin_main(){
    return array();
}

/**
 * Phase 1 of the installer
 *
 * @param
 * @returns array
 * @return array of language values
 */
function installer_admin_phase1() {
    return array('languages' => array('eng' => 'English'));
}

function installer_admin_phase2() {
    return array();
}

function installer_admin_phase3()
{
    global $HTTP_POST_VARS;
    if ($HTTP_POST_VARS['agree'] != 'agree') {
        // didn't agree to license, don't install
        pnRedirect('install.php');
    }

    return array();
}

/**
 * Phase 4 of the installer
 *
 * @returns array
 * @return array of default values for the database creation
 */
function installer_admin_phase4()
{
    // Get default values from config files
    $dbHost   = pnCore_getSystemVar('DB.Host');
    $dbUser   = pnCore_getSystemVar('DB.UserName');
    $dbPass   = pnCore_getSystemvar('DB.Password');
    $dbName   = pnCore_getSystemvar('DB.Name');
    $dbPrefix = pnCore_getSystemvar('DB.TablePrefix');

    return array('database_host' => $dbHost,
                 'database_username' => $dbUser,
                 'database_password' => $dbPass,
                 'database_name' => $dbName,
                 'database_prefix' => $dbPrefix,
                 'database_types' => array('mysql'    => 'MySQL',
                                           'postgres' => 'Postgres'));
}

function installer_adminapi_phase5()
{
    global $HTTP_POST_VARS;

    $dbInfo['dbHost'] = $HTTP_POST_VARS['install_database_host'];
    $dbInfo['dbName'] = $HTTP_POST_VARS['install_database_name'];
    $dbInfo['dbUname'] = $HTTP_POST_VARS['install_database_username'];
    $dbInfo['dbPass'] = $HTTP_POST_VARS['install_database_password'];
    $dbInfo['prefix'] = $HTTP_POST_VARS['install_database_prefix'];
    $dbInfo['dbType'] = $HTTP_POST_VARS['install_database_type'];

    if (isset($HTTP_POST_VARS['install_create_database'])) {
    //Ugly Switch... until we write a database connection wrapper
    //Needed because ADONewConnection requires a database to connect to
        switch($dbtype){
            case 'mysql':
            //TODO: add error checking (prolly wait til the connection wrapper)
            mysql_connect($dbhost,$dbuser,$dbpass);
            break;
        }

        //TODO: add error checking and replace with pnDBCreateDB
        mysql_create_db($dbname);
    }

    if (isset($HTTP_POST_VARS['install_intranet'])) {
        $intranet = true;
    } else {
        $intranet = false;
    }

    // Save config data
    installer_adminapi_modifyconfig($dbInfo);

    // Kick it
    pnCoreInit(PNCORE_SYSTEM_ADODB);

    // install modules module
    $mod_init_file = 'modules/modules/pninit.php';

    if (file_exists($mod_init_file)) {
        include_once ($mod_init_file);
    } else {
        // modules/base/pninit.php not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $mod_init_file doesn't exist."));return;
    }

    // Run the function, check for existence
    $mod_func = 'modules_init';

    if (function_exists($mod_func)) {
        $res = $mod_func();
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
        // modules_init() not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $mod_func doesn't exist."));return;
    }

    // Initialize *minimal* tableset
    // Load the installer module, the hard way - file check too
    $base_init_file = 'modules/base/pninit.php';

    if (file_exists($base_init_file)) {
        include_once ($base_init_file);
    } else {
        // modules/base/pninit.php not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module file $base_init_file doesn't exist."));return;
    }

    // Run the function, check for existence
    $mod_func = 'base_init';

    if (function_exists($mod_func)) {
        $res = $mod_func();
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
        // base_init() not found?!
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module API function $mod_func doesn't exist."));return;
    }

    // log user in

    pnRedirect('index.php?module=installer&type=admin&func=bootstrap');
}
function installer_admin_bootstrap()
{
    // log in admin user
    $res = pnUserLogIn('admin', 'password', false);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // load modules API
    $res = pnModAPILoad('modules', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // initialize & activate adminpanels module
    $res = pnModAPIFunc('modules', 'admin', 'initialise', array('regid' => pnModGetIDFromName('adminpanels')));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    $res = pnModAPIFunc('modules', 'admin', 'setstate', array('regid' => pnModGetIDFromName('adminpanels'),
                                                              'state' => _PNMODULE_STATE_ACTIVE));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    pnRedirect(pnModURL('installer', 'admin', 'create_administrator'));
    return array();
}

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

function installer_admin_finish()
{
    $res = pnModAPILoad('blocks', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    // Load up database
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $block_groups_table          = $pntable['block_groups'];
    
    $query = "SELECT    pn_id as id
              FROM      $block_groups_table
              WHERE     pn_name = 'left'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = pnML("Group 'left' not found.");
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    list ($group_id) = $result->fields;
    
    $type_id = pnBlockTypeExists('adminpanels', 'adminmenu');
    
    $block_id = pnModAPIFunc('blocks',
                             'admin',
                             'create_instance', array('title'    => 'Admin',
                                                      'type'     => $type_id,
                                                      'group'    => $group_id,
                                                      'template' => '',
                                                      'state'    => 2));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    $msg = pnML('Reminder message body will go here.');
    
    $type_id = pnBlockTypeExists('base', 'html');
    $block_id = pnModAPIFunc('blocks',
                             'admin',
                             'create_instance', array('title'    => 'Reminder',
                                                      'content'  => $msg,
                                                      'type'     => $type_id,
                                                      'group'    => $group_id,
                                                      'template' => '',
                                                      'state'    => 2));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }
    
    return array();
}

function installer_admin_modifyconfig(){}

?>
