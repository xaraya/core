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
    if (!isset($modified)) return;



    if (isset($HTTP_POST_VARS['install_create_database'])) {
        $res = pnInstallAPIFunc('installer',
                                'admin',
                                'createdb');
        // TODO: Exception!
        if (!isset($res)) die('could not create a database');
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
    if(!isset($res)) die('could not install or upgrade');

    // Initialize *minimal* tableset

    // log user in
    pnResponseRedirect('index.php?module=installer&type=admin&func=bootstrap');
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
