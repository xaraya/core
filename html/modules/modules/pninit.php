<?php
// $Id$
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
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Johnny Robeson
// Purpose of file:  Initialisation functions for modules module
// ----------------------------------------------------------------------

// Load Table Maintainance API
pnDBLoadTableMaintenanceAPI();

/**
 * Initialise the modules module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    list($dbconn) = pnDBGetConn();
    $tables = pnDBGetTables();

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the module system
     *
     * prefix_modules       - basic module info
     * prefix_module_states - table to hold states for unshared modules
     * prefix_module_vars   - module variables table
     * prefix_hooks         - table for hooks
     ********************************************************************/

    // prefix_modules
    /*********************************************************************
    * CREATE TABLE pn_modules (
    *  pn_id int(11) NOT NULL auto_increment,
    *  pn_name varchar(64) NOT NULL default '',
    *  pn_regid int(10) unsigned NOT NULL default '0',
    *  pn_directory varchar(64) NOT NULL default '',
    *  pn_version varchar(10) NOT NULL default '0',
    *  pn_mode int(6) NOT NULL default '1',
    *  pn_class varchar(64) NOT NULL default '',
    *  pn_category varchar(64) NOT NULL default '',
    *  pn_admin_capable tinyint(1) NOT NULL default '0',
    *  pn_user_capable tinyint(1) NOT NULL default '0',
    *  PRIMARY KEY  (pn_id)
    * )
    *********************************************************************/
    $fields = array(
    'pn_id'             => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_name'           => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_regid'          => array('type'=>'integer','unsigned'=>true,'null'=>false,'default'=>'0'),
    'pn_directory'      => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_version'        => array('type'=>'varchar','size'=>10,'null'=>false),
    'pn_mode'           => array('type'=>'integer','size'=>'small','null'=>false,'default'=>'1'),
    'pn_class'          => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_category'       => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_admin_capable'  => array('type'=>'integer','size'=>'tiny','null'=>false,'default'=>'0'),
    'pn_user_capable'   => array('type'=>'integer','size'=>'tiny','null'=>false,'default'=>'0')
    );

    $query = pnDBCreateTable($tables['modules'],$fields);
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // prefix_module_states
    /********************************************************************
    * CREATE TABLE pn_module_states (
    *  pn_regid int(11) unsigned NOT NULL default '0',
    *  pn_state tinyint(1) NOT NULL default '0',
    *  PRIMARY KEY  (pn_regid)
    * )
    ********************************************************************/
    $fields = array(
    'pn_regid' => array('type'=>'integer','null'=>false,'unsigned'=>true,'primary_key'=>false),
    'pn_state' => array('type'=>'integer','null'=>false,'default'=>'0')
    );

    $query = pnDBCreateTable($tables['system/module_states'],$fields);
    
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // prefix_module_vars
    /********************************************************************
    * CREATE TABLE pn_module_vars (
    *  pn_id int(11) NOT NULL auto_increment,
    *  pn_modname varchar(64) NOT NULL default '',
    *  pn_name varchar(64) NOT NULL default '',
    *  pn_value longtext,
    *  PRIMARY KEY  (pn_id)
    * )
    ********************************************************************/
    $fields = array(
    'pn_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_modname' => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_name'    => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_value'   => array('type'=>'text','size'=>'long')
    );

    $query = pnDBCreateTable($tables['module_vars'],$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // prefix_hooks
    /********************************************************************
    * CREATE TABLE pn_hooks (
    *  pn_id int(10) unsigned NOT NULL auto_increment,
    *  pn_object varchar(64) NOT NULL default '',
    *  pn_action varchar(64) NOT NULL default '',
    *  pn_smodule varchar(64) default NULL,
    *  pn_stype varchar(64) default NULL,
    *  pn_tarea varchar(64) NOT NULL default '',
    *  pn_tmodule varchar(64) NOT NULL default '',
    *  pn_ttype varchar(64) NOT NULL default '',
    *  pn_tfunc varchar(64) NOT NULL default '',
    *  PRIMARY KEY  (pn_id)
    * )
    *********************************************************************/
    $fields = array(
    'pn_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'pn_object'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_action'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_smodule' => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_stype'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_tarea'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_tmodule' => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_ttype'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'pn_tfunc'   => array('type'=>'varchar','size'=>64,'null'=>false)
    );
     
    $query = pnDBCreateTable($tables['hooks'],$fields);

    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Initialisation successful
    return true;
}

/**
 * Upgrade the modules module from an old version
 *
 * @param oldversion the old version to upgrade from
 * @returns bool
 */
function modules_upgrade($oldversion)
{
    return false;
}

/**
 * Delete the modules module
 *
 * @param none
 * @returns bool
 */
function modules_delete()
{
    return false;
}
