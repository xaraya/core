<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 *
 * Modules System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage modules module
 * @author Xaraya Team
*/

// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

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
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $sitePrefix   = xarDBGetSiteTablePrefix();
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables['modules']          = $systemPrefix . '_modules';
    $tables['module_states']    = $sitePrefix   . '_module_states';
    $tables['module_vars']      = $sitePrefix   . '_module_vars';
    $tables['module_uservars']  = $sitePrefix   . '_module_uservars';
    $tables['hooks']            = $sitePrefix   . '_hooks';
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
    * CREATE TABLE xar_modules (
    *  xar_id int(11) NOT NULL auto_increment,
    *  xar_name varchar(64) NOT NULL default '',
    *  xar_regid int(10) unsigned NOT NULL default '0',
    *  xar_directory varchar(64) NOT NULL default '',
    *  xar_version varchar(10) NOT NULL default '0',
    *  xar_mode int(6) NOT NULL default '1',
    *  xar_class varchar(64) NOT NULL default '',
    *  xar_category varchar(64) NOT NULL default '',
    *  xar_admin_capable tinyint(1) NOT NULL default '0',
    *  xar_user_capable tinyint(1) NOT NULL default '0',
    *  PRIMARY KEY  (xar_id)
    * )
    *********************************************************************/
    $fields = array(
    'xar_id'             => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'           => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_regid'          => array('type'=>'integer','unsigned'=>true,'null'=>false,'default'=>'0'),
    'xar_directory'      => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_version'        => array('type'=>'varchar','size'=>10,'null'=>false),
    'xar_mode'           => array('type'=>'integer','size'=>'small','null'=>false,'default'=>'1'),
    'xar_class'          => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_category'       => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_admin_capable'  => array('type'=>'integer','size'=>'tiny','null'=>false,'default'=>'0'),
    'xar_user_capable'   => array('type'=>'integer','size'=>'tiny','null'=>false,'default'=>'0')
    );

    $query = xarDBCreateTable($tables['modules'],$fields);

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // prefix_module_states
    /********************************************************************
    * CREATE TABLE xar_module_states (
    *  xar_regid int(11) unsigned NOT NULL default '0',
    *  xar_state tinyint(1) NOT NULL default '0',
    *  PRIMARY KEY  (xar_regid)
    * )
    ********************************************************************/
    $fields = array(
    'xar_regid' => array('type'=>'integer','null'=>false,'unsigned'=>true,'primary_key'=>false),
    'xar_state' => array('type'=>'integer','null'=>false,'default'=>'0')
    );

    $query = xarDBCreateTable($tables['module_states'],$fields);

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    $index = array('name'   => 'i_xar_regid',
                   'fields' => array('xar_regid'));

    $query = xarDBCreateIndex($tables['module_states'],$index);

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // prefix_module_vars
    /********************************************************************
    * CREATE TABLE xar_module_vars (
    *  xar_id int(11) NOT NULL auto_increment,
    *  xar_mod_id int(11) NOT NULL default 0,
    *  xar_name varchar(64) NOT NULL default '',
    *  xar_value longtext,
    *  PRIMARY KEY  (xar_id)
    * )
    ********************************************************************/
    $fields = array(
    'xar_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_modid'   => array('type'=>'integer','null'=>false),
    'xar_name'    => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_value'   => array('type'=>'text','size'=>'long')
    );

    $query = xarDBCreateTable($tables['module_vars'],$fields);
    $result =& $dbconn->Execute($query);
    if(!$result) return;

    $index = array('name'   => 'i_xar_modid',
                   'fields' => array('xar_modid'));

    $query = xarDBCreateIndex($tables['module_vars'],$index);

    $result =& $dbconn->Execute($query);
    if(!$result) return;



    // prefix_module_uservars
    /********************************************************************
    * CREATE TABLE xar_module_uservars (
    *  xar_mvid int(11) NOT NULL auto_increment,
    *  xar_uid  int(11) NOT NULL default 0,
    *  xar_value longtext,
    *  PRIMARY KEY  (xar_mvid, xar_uid)
    * )
    ********************************************************************/
    $fields = array(
    'xar_mvid'  => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_uid'   => array('type'=>'integer','null'=>false,'unsigned'=>true, 'primary_key'=>true),
    'xar_value' => array('type'=>'text','size'=>'long')
    );

    $query = xarDBCreateTable($tables['module_uservars'],$fields);

    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // MrB: do we want an index on xar_value, on large sites, lots of records may exist
    // Pro: searching for values will speed up (is that used somewhere)
    // Con: setting a user mod var will become slower and slower (relatively tho)

    // prefix_hooks
    /********************************************************************
    * CREATE TABLE xar_hooks (
    *  xar_id int(10) unsigned NOT NULL auto_increment,
    *  xar_object varchar(64) NOT NULL default '',
    *  xar_action varchar(64) NOT NULL default '',
    *  xar_smodule varchar(64) default NULL,
    *  xar_stype varchar(64) default NULL,
    *  xar_tarea varchar(64) NOT NULL default '',
    *  xar_tmodule varchar(64) NOT NULL default '',
    *  xar_ttype varchar(64) NOT NULL default '',
    *  xar_tfunc varchar(64) NOT NULL default '',
    *  PRIMARY KEY  (xar_id)
    * )
    *********************************************************************/
    $fields = array(
    'xar_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_object'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_action'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_smodule' => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_stype'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_tarea'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_tmodule' => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_ttype'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_tfunc'   => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_order'   => array('type'=>'integer','null'=>false,'default'=>'0')
    );

    $query = xarDBCreateTable($tables['hooks'],$fields);

    $result =& $dbconn->Execute($query);
    if(!$result) return;
    
    // <andyv> Add module variables for default user/admin, used in modules list
    /********************************************************************
    * at this stage of installer mod vars cannot be set, so we use DB calls
    * prolly need to move this closer to installer, not sure yet
    *********************************************************************/
    // default show-hide core modules
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'hidecore',0)";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default regenerate command
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'regen',0)";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default style of module list
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'selstyle','plain')";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default filtering based on module states
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'selfilter', 0)";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default modules list sorting order
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'selsort','nameasc')";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default show-hide modules statistics
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'hidestats',0)";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default maximum number of modules listed per page
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'selmax','all')";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    // default start page
    $query = "INSERT INTO ".$tables['module_vars']." (xar_id, xar_modid, xar_name, xar_value) 
    VALUES (".$dbconn->GenId($tables['module_vars']).",1,'startpage','overview')";
    $result =& $dbconn->Execute($query);
    if(!$result) return;
    
    // Initialisation successful
    return true;
}

/**
 * Activates the modules module
 *
 * @param none
 * @returns bool
 *
 */
function modules_activate()
{
    // make sure we dont miss empty variables (which were not passed thru)
    if(empty($selstyle)) $selstyle                  = 'plain';
    if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    if(empty($hidecore)) $hidecore                  = 0;
    if(empty($selsort)) $selsort                    = 'namedesc';

    xarModSetVar('modules', 'hidecore', $hidecore);
    xarModSetVar('modules', 'selstyle', $selstyle);
    xarModSetVar('modules', 'selfilter', $selfilter);
    xarModSetVar('modules', 'selsort', $selsort);

    return true;
}

/**
 * Upgrade the modules module from an old version
 *
 * @param oldversion the old version to upgrade from
 * @returns bool
 * @todo include setting moduservars in next upgrade (2.1)
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
    // this module cannot be removed
    return false;
}
?>