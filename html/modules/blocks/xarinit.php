<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $prefix = xarConfigGetVar('prefix');
    // Create tables

    // *_block_groups
    $query = xarDBCreateTable($prefix . '_block_groups',
                             array('xar_id'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_name'        => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => '')));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    $query = xarDBCreateIndex($prefix . '_block_groups',
                             array('name'   => 'xar_name_index',
                                   'fields' => array('xar_name'),
                                   'unique' => 'true'));
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_block_instances
    $query = xarDBCreateTable($prefix . '_block_instances',
                             array('xar_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_type_id'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_title'       => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'xar_content'     => array('type'        => 'text',
                                                             'null'        => false),
                                   'xar_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'xar_state'       => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '2'),
                                   'xar_refresh'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_last_update' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));

     $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // *_block_types
    $query = xarDBCreateTable($prefix . '_block_types',
                             array('xar_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_type'        => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_module'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => '')));

     $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = xarDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'xar_type_index',
                                   'fields' => array('xar_type'),
                                   'unique' => false));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
/*
    TODO: Find a fix for this - Postgres will not allow partial indexes
    $query = xarDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'xar_typemodule_index',
                                   'fields' => array('xar_type(50)', 'xar_module(50)'),
                                   'unique' => true));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
*/    
    // *_block_group_instances
    $query = xarDBCreateTable($prefix . '_block_group_instances',
                             array('xar_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_group_id'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_instance_id' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_position'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    // *_userblocks
    $query = xarDBCreateTable($prefix . '_userblocks',
                             array('xar_uid'         => array('type'    => 'integer',
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'xar_bid'         => array('type'    => 'varchar',
                                                             'size'    => 32,
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'xar_active'      => array('type'    => 'integer',
                                                             'size'    => 'tiny',
                                                             'null'    => false,
                                                             'default' => '1'),
                                   'xar_last_update' => array('type'    => 'timestamp',
                                                             'null'    => false)));
    
     $dbconn->Execute($query);
        
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }
    
    $query = xarDBCreateIndex($prefix . '_userblocks',
                             array('name'   => 'xar_uidbid_index',
                                   'fields' => array('xar_uid', 'xar_bid'),
                                   'unique' => true));
    $dbconn->Execute($query);
    
    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Register BL tags
    xarTplRegisterTag('blocks', 'blocks-stateicon',
                     array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
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
