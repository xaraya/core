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
        pnResponseRedirect('install.php');
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

function installer_admin_phase5()
{
    global $HTTP_POST_VARS;

    $dbHost      = $HTTP_POST_VARS['install_database_host'];
    $dbName      = $HTTP_POST_VARS['install_database_name'];
    $dbUname     = $HTTP_POST_VARS['install_database_username'];
    $dbPass      = $HTTP_POST_VARS['install_database_password'];
    $dbPrefix    = $HTTP_POST_VARS['install_database_prefix'];
    $dbType      = $HTTP_POST_VARS['install_database_type'];
    $installType = $HTTP_POST_VARS['install_type'];

    if (isset($HTTP_POST_VARS['install_intranet'])) {
        $intranet = true;
    } else {
        $intranet = false;
    }

    pnInstallAPILoad('installer','admin');

    // Save config data
    $modified = pnInstallAPIFunc('installer',
                                 'admin',
                                 'modifyconfig',
                                 array('dbHost'    => $dbHost,
                                       'dbName'    => $dbName,
                                       'dbUname'   => $dbUname,
                                       'dbPass'    => $dbPass,
                                       'dbPrefix'  => $dbPrefix,
                                       'dbType'    => $dbType));
    // throw back
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }



    if (isset($HTTP_POST_VARS['install_create_database'])) {
        $res = pnInstallAPIFunc('installer',
                                'admin',
                                'createdb');
        // TODO: Exception!
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return NULL;
        }
    }

    switch($HTTP_POST_VARS['install_type']){
        case 'new':
                $initFunc = 'init';
                 break;
        case 'upgrade':
                 $initFunc = 'upgrade';
                 break;
    }


    // Start the database
    pnCoreInit(PNCORE_SYSTEM_ADODB);

    // Load in modules/installer/pninit.php and choose a new install or upgrade
    $res = pnInstallAPIFunc('installer',
                            'admin',
                            'initialise',
                            array('directory' => 'installer',
                                  'initfunc'  => $initFunc));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
    }

    return array();
}

/*function installer_admin_phase6
{


    return array();
}*/

/**
 * Bootstrap Xaraya
 *
 * @param none
 * @returns bool
 */
function installer_admin_bootstrap()
{


    // log in admin user
    $res = pnUserLogIn('admin', 'password', 0);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    // Activate modules
    $res = pnModAPILoad('installer',
                        'admin'.
                        'initialise',
                        array('directory' => 'installer',
                              'initfunc' => 'activate'));
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

    $res = pnModAPIFunc('users', 'admin', 'update', array('uid'   => 2,
                                                          'name'  => $name,
                                                          'uname' => $username,
                                                          'email' => $email,
                                                          'pass'  => $password,
                                                          'url'   => $url));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return;
    }

    pnResponseRedirect(pnModURL('installer', 'admin', 'finish'));
}



function installer_admin_finish()
{
    $res = pnModAPILoad('blocks', 'admin');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return NULL;
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
