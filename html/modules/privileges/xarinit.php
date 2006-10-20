<?php
/**
 * Initialisation functions for the security module
 *
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 */

 /**
 * Purpose of file:  Initialisation functions for the security module
 * Initialise the privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @param none
 * @returns bool
 * @throws DATABASE_ERROR
 */
function privileges_init()
{
    /*    if(!xarModIsAvailable('roles')) {
     $msg=xarML('The roles module should be activated first');
     throw new Exception($msg);
     }
    */

    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $tables['privileges'] = $sitePrefix . '_privileges';
    $tables['privmembers'] = $sitePrefix . '_privmembers';
    $tables['security_acl'] = $sitePrefix . '_security_acl';
    $tables['security_masks'] = $sitePrefix . '_security_masks';
    $tables['security_instances'] = $sitePrefix . '_security_instances';
    $tables['security_realms']      = $sitePrefix . '_security_realms';
    $tables['security_privsets']      = $sitePrefix . '_security_privsets';

    // All or nothing
    try {
        $dbconn->begin();

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
        
        /*********************************************************************
         * CREATE TABLE xar_security_realms (
         *  xar_rid int(11) NOT NULL auto_increment,
         *  xar_name varchar(255) NOT NULL default '',
         *  PRIMARY KEY  (xar_rid)
         * )
         *********************************************************************/
        $fields = array('xar_rid'  => array('type'        => 'integer','null'        => false,
                                            'default'     => '0',      'increment'   => true,
                                            'primary_key' => true),
                        'xar_name' => array('type'        => 'varchar','size'        => 255,
                                            'null'        => false,    'default'     => ''));
        $query = xarDBCreateTable($tables['security_realms'],$fields);
        $dbconn->Execute($query);
        
        
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
        
        $fields = array('xar_pid'   => array('type' => 'integer', 'null' => false, 'default' => '0','increment' => true, 'primary_key' => true),
                        'xar_name'  => array('type' => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'xar_realmid'=>array('type' => 'integer', 'null' => true, 'default' => null),
                        // TODO: use modid here
                        'xar_module' => array('type'=> 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'xar_component' => array('type'  => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'xar_instance' => array('type'   => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'xar_level' => array('type'      => 'integer', 'null' => false,'default' => '0'),
                        'xar_description' => array('type'=> 'varchar', 'size' => 255, 'null' => false, 'default'     => ''));
        $query = xarDBCreateTable($tables['privileges'],$fields);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_privileges_name',
                       'fields'    => array('xar_name'),
                       'unique'    => true);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);
        
        $index = array('name'      => 'i_'.$sitePrefix.'_privileges_realmid',
                       'fields'    => array('xar_realmid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_privileges_module',
                       'fields'    => array('xar_module'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_privileges_level',
                       'fields'    => array('xar_level'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        xarDB::importTables(array('privileges' => xarDBGetSiteTablePrefix() . '_privileges'));
        
        /*********************************************************************
         * CREATE TABLE xar_privmembers (
         *   xar_pid int(11) NOT NULL default '0',
         *   xar_parentid int(11) NOT NULL default '0',
         *   PRIMARY KEY xar_pid (xar_pid,xar_parentid)
         * )
         *********************************************************************/
        
        $query = xarDBCreateTable($tables['privmembers'],
                                  array('xar_pid'       => array('type'        => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => '0',
                                                                 'primary_key' => true),
                                        'xar_parentid'      => array('type'        => 'integer',
                                                                     'null'        => false,
                                                                     'default'     => '0',
                                                                     'primary_key' => true)));
        $dbconn->Execute($query);
        
        xarDB::importTables(array('privmembers' => xarDBGetSiteTablePrefix() . '_privmembers'));
        
        $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_pid',
                       'fields'    => array('xar_pid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privmembers'],$index);
        $dbconn->Execute($query);
                
        $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_parentid',
                       'fields'    => array('xar_parentid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privmembers'],$index);
        $dbconn->Execute($query);

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
                                                                    'primary_key'         => true),
                                        'xar_permid'      => array('type'   => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0',
                                                                   'primary_key'         => true)));
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_security_acl_partid',
                       'fields'    => array('xar_partid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_acl'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_security_acl_permid',
                       'fields'    => array('xar_permid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_acl'],$index);
        $dbconn->Execute($query);

        xarDB::importTables(array('security_acl' => xarDBGetSiteTablePrefix() . '_security_acl'));
        
        /*********************************************************************
         * CREATE TABLE xar_security_masks (
         *   xar_sid int(11) NOT NULL default '0',
         *   xar_name varchar(100) NOT NULL default '',
         *   xar_realm varchar(100) NOT NULL default '',
         *   xar_modid int(11) NOT NULL default '0',
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
        
        $fields = array(
                        'xar_sid'  => array('type'=> 'integer','null'=> false,'default'=>'0','increment'=>true,'primary_key' => true),
                        'xar_name' => array('type'=>'varchar','size'=>100,'null'=>false,'default'=>''),
                        'xar_realm'=> array('type'=>'varchar','size'=>100,'null'=> false,'default'=>''),
                        'xar_modid'=> array('type'=>'integer','unsigned'=>true,'null'=>false,'default'=>'0'),
                        'xar_component'=>array('type'=>'varchar','size'=>100,'null'=>false,'default'=>''),
                        'xar_instance' => array('type'=>'varchar','size'=> 100,'null'=>false,'default'=>''),
                        'xar_level' => array('type'=>'integer','null'=> false,'default'=>'0'),
                        'xar_description' => array('type'=> 'varchar','size'=>255,'null'=>false,'default'=>''));
        $query = xarDBCreateTable($tables['security_masks'],$fields);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_realm',
                       'fields'    => array('xar_realm'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_masks'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_module',
                       'fields'    => array('xar_modid'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_masks'],$index);
        $dbconn->Execute($query);
        
        $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_level',
                       'fields'    => array('xar_level'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_masks'],$index);
        $dbconn->Execute($query);

        xarDB::importTables(array('security_masks' => xarDBGetSiteTablePrefix() . '_security_masks'));
        
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
                                        'xar_modid' => array('type'     => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
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
        
        $dbconn->Execute($query);
        
        xarDB::importTables(array('security_instances' => xarDBGetSiteTablePrefix() . '_security_instances'));
        
        /*********************************************************************
         * CREATE TABLE xar_security_privsets (
         *  xar_uid int(11) NOT NULL auto_increment,
         *  xar_set varchar(255) NOT NULL default '',
         *  PRIMARY KEY  (xar_rid)
         * )
         *********************************************************************/
        /*
         $query = xarDBCreateTable($tables['security_privsets'],
         array('xar_uid'  => array('type'        => 'integer',
         'null'        => false,
         'default'     => '0',
         'increment'   => true,
         'primary_key' => true),
         'xar_set' => array('type'        => 'text')));
         
         TO BE IMPLEMENTED LATER
         $result = $dbconn->Execute($query);
         
         xarDB::importTables(array('security_instances' => xarDBGetSiteTablePrefix() . '_security_instances'));
         
        */
        $dbconn->commit();
        // Set up an initial value for module variables.
        
        // Initialisation successful
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

function privileges_activate()
{
    // On activation, set our variables
    xarModSetVar('privileges', 'showrealms', false);
    xarModSetVar('privileges', 'inheritdeny', true);
    xarModSetVar('privileges', 'tester', 0);
    xarModSetVar('privileges', 'test', false);
    xarModSetVar('privileges', 'testdeny', false);
    xarModSetVar('privileges', 'testmask', 'All');
    xarModSetVar('privileges', 'realmvalue', 'none');
    xarModSetVar('privileges', 'realmcomparison','exact');
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
    switch($oldVersion) {
    case '0.1.0':
        if (!xarModAPIFunc('privileges','admin','createobjects')) return;
        break;
    }
    return true;
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
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    xarDBLoadTableMaintenanceAPI();

    // TODO: wrap in transaction? (this section is only for testing anyways)
    $query = xarDBDropTable($tables['privileges']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['privmembers']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_realms']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_acl']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_masks']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_instances']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    return true;
}

?>
