<?php
/**
 * File: $Id$
 *
 * Purpose of file:  Initialisation functions for the security module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage Security Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

// Load Table Maintainance API

/**
 * Initialise the privileges module
 *
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function privileges_init()
{
   /* if(!xarModIsAvailable('roles')) {
        $msg=xarML('The roles module should be activated first');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,'MODULE_DEPENDENCY',
                        new SystemException($msg));
        return;
    }
    */
 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['privileges'] = $sitePrefix . '_privileges';
    $tables['privmembers'] = $sitePrefix . '_privmembers';
    $tables['security_acl'] = $sitePrefix . '_security_acl';
    $tables['security_masks'] = $sitePrefix . '_security_masks';
    $tables['security_instances'] = $sitePrefix . '_security_instances';
    $tables['security_realms']      = $sitePrefix . '_security_realms';
    $tables['security_privsets']      = $sitePrefix . '_security_privsets';

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the privileges module
     *
     * prefix_privileges       - holds privileges info
     * prefix_privmembers      - holds info on privileges group membership
     * prefix_security_acl     - holds info on privileges assignments to roles
     * prefix_security_masks   - holds info on masks for security checks
     * prefix_security_instances       - holds module instance definitions
     * prefix_security_realms  - holds realsm info
     ********************************************************************/

    // prefix_security_realms
    /*********************************************************************
    * CREATE TABLE xar_security_realms (
    *  xar_rid int(11) NOT NULL auto_increment,
    *  xar_name varchar(255) NOT NULL default '',
    *  PRIMARY KEY  (xar_rid)
    * )
    *********************************************************************/
    $query = xarDBCreateTable($tables['security_realms'],
             array('xar_rid'  => array('type'        => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'        => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // prefix_privileges
    /*********************************************************************
    * CREATE TABLE xar_privileges (
    *   xar_pid int(11) NOT NULL auto_increment,
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_realm varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_component varchar(100) NOT NULL default '',
    *   xar_instance varchar(100) NOT NULL default '',
    *   xar_level int(11) NOT NULL default '0',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_pid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privileges'],
             array('xar_pid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

   if (!$dbconn->Execute($query)) return;

    xarDB_importTables(array('privileges' => xarDBGetSiteTablePrefix() . '_privileges'));

    // prefix_privmembers
    /*********************************************************************
    * CREATE TABLE xar_privmembers (
    *   xar_pid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privmembers'],
             array('xar_pid'       => array('type'       => 'integer',
                                           'null'        => false,
                                           'default'     => '0'),
                   'xar_parentid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0')));
    if (!$dbconn->Execute($query)) return;

    xarDB_importTables(array('privmembers' => xarDBGetSiteTablePrefix() . '_privmembers'));

    $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_pid',
                   'fields'    => array('xar_pid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privmembers'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_parentid',
                   'fields'    => array('xar_parentid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privmembers'],$index);
    if (!$dbconn->Execute($query)) return;

    // prefix_security_acl
    /*********************************************************************
    * CREATE TABLE xar_security_acl (
    *   xar_partmember int(11) NOT NULL default '0',
    *   xar_permmember int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_acl'],
             array('xar_partid'       => array('type'  => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true),
                   'xar_permid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true)));
    if (!$dbconn->Execute($query)) return;

    xarDB_importTables(array('security_acl' => xarDBGetSiteTablePrefix() . '_security_acl'));

    // prefix_security_masks
    /*********************************************************************
    * CREATE TABLE xar_security_masks (
    *   xar_sid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_realm varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_component varchar(100) NOT NULL default '',
    *   xar_instance varchar(100) NOT NULL default '',
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
    *   xar_level int(11) NOT NULL default '0',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_masks'],
             array('xar_sid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    xarDB_importTables(array('security_masks' => xarDBGetSiteTablePrefix() . '_security_masks'));

    // prefix_security_instances
    /*********************************************************************
    * CREATE TABLE xar_security_instances (
    *   xar_iid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_type varchar(100) NOT NULL default '',
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_instances'],
             array('xar_iid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_header' => array('type'   => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_query' => array('type'   => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_limit' => array('type'  => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_propagate' => array('type'  => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_instancetable2' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancechildid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instanceparentid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    xarDB_importTables(array('security_instances' => xarDBGetSiteTablePrefix() . '_security_instances'));

    // prefix_security_privsets
    /*********************************************************************
    * CREATE TABLE xar_security_privsets (
    *  xar_uid int(11) NOT NULL auto_increment,
    *  xar_set varchar(255) NOT NULL default '',
    *  PRIMARY KEY  (xar_rid)
    * )
    *********************************************************************/
    $query = xarDBCreateTable($tables['security_privsets'],
             array('xar_uid'  => array('type'        => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_set' => array('type'        => 'text')));

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarDB_importTables(array('security_instances' => xarDBGetSiteTablePrefix() . '_security_instances'));

    // Initialisation successful
    return true;
}

/**
 * Upgrade the privileges module from an old version
 *
 * @param oldVersion the old version to upgrade from
 * @returns bool
 */
function privileges_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the privileges module
 *
 * @param none
 * @returns boolean
 */
function privileges_delete()
{
    // this module cannot be removed
    return false;

    /*********************************************************************
    * Drop the tables
    *********************************************************************/

 // Get database information
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    $query = xarDBDropTable($tables['privileges']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['privmembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_realms']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_acl']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_masks']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_instances']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    return true;
}

?>
