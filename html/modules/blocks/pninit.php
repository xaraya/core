<?php
// $Id$
// ----------------------------------------------------------------------
// POST-NUKE Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// Based on:
// PHP-NUKE Web Portal System - http://phpnuke.org/
// Thatware - http://thatware.org/
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
// Original Author of file: Paul Rosania
// Purpose of file:  Initialisation functions for blocks
// ----------------------------------------------------------------------

/**
 * initialise the blocks module
 */
function blocks_init()
{
    // Get database information
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $prefix = pnConfigGetVar('prefix');
    // Create tables

    // *_block_groups
    $query = pnDBCreateTable($prefix . '_block_groups',
                             array('pn_id'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_name'        => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => '')));
    $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = pnDBCreateIndex($prefix . '_block_groups',
                             array('name'   => 'pn_name_index',
                                   'fields' => array('pn_name'),
                                   'unique' => 'true'));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_block_instances
    $query = pnDBCreateTable($prefix . '_block_instances',
                             array('pn_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_type_id'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'pn_title'       => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'pn_content'     => array('type'        => 'text',
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'pn_state'       => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '2'),
                                   'pn_refresh'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'pn_last_update' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
                                                                               
    // *_block_types
    $query = pnDBCreateTable($prefix . '_block_types',
                             array('pn_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_type'        => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'pn_module'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => '')));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = pnDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'pn_type_index',
                                   'fields' => array('pn_type'),
                                   'unique' => false));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = pnDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'pn_typemodule_index',
                                   'fields' => array('pn_type(50)', 'pn_module(50)'),
                                   'unique' => true));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_block_group_instances
    $query = pnDBCreateTable($prefix . '_block_group_instances',
                             array('pn_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'pn_group_id'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'pn_instance_id' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'pn_position'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_userblocks
    $query = pnDBCreateTable($prefix . '_userblocks',
                             array('pn_uid'         => array('type'    => 'integer',
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'pn_bid'         => array('type'    => 'varchar',
                                                             'size'    => 32,
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'pn_active'      => array('type'    => 'integer',
                                                             'size'    => 'tiny',
                                                             'null'    => false,
                                                             'default' => '1'),
                                   'pn_last_update' => array('type'    => 'timestamp',
                                                             'null'    => false)));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = pnDBCreateIndex($prefix . '_userblocks',
                             array('name'   => 'pn_uidbid_index',
                                   'fields' => array('pn_uid', 'pn_bid'),
                                   'unique' => true));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // Create default block groups/instances
    pnModAPILoad('blocks', 'admin');
    pnModAPIFunc('blocks', 'admin', 'create_group', array('name' => 'left'));
    pnModAPIFunc('blocks', 'admin', 'create_group', array('name'     => 'right',
                                                          'template' => 'right'));
    

    // Register BL tags
    pnTplRegisterTag('blocks', 'blocks-stateicon',
                     array(new pnTemplateAttribute('bid', PN_TPL_STRING|PN_TPL_REQUIRED)),
                     'blocks_userapi_handleStateIconTag');
    
    // Initialisation successful
    return true;
}

/**
 * upgrade the blocks module from an old version
 */
function blocks_upgrade($oldversion)
{
    return false;
}

/**
 * delete the blocks module
 */
function blocks_delete()
{
    return false;
}

?>
