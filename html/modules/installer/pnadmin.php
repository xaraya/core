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

function installer_admin_main(){}
// entry point for the installer

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