<?php
/**
 * Initialisation functions for the security module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 * @link http://xaraya.com/index.php/release/1098.html
 */

sys::import('xaraya.tableddl');

 /**
 * Initialise the privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @returns bool
 * @throws DATABASE_ERROR
 */
function privileges_init()
{
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['privileges'] = $prefix . '_privileges';
    $tables['privmembers'] = $prefix . '_privmembers';
    $tables['security_acl'] = $prefix . '_security_acl';
    $tables['security_instances'] = $prefix . '_security_instances';
    $tables['security_realms']      = $prefix . '_security_realms';
    $tables['security_privsets']      = $prefix . '_security_privsets';

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
         *  id int(11) NOT NULL auto_increment,
         *  name varchar(255) NOT NULL default '',
         *  PRIMARY KEY  (id)
         * )
         *********************************************************************/
        $fields = array('id'  => array('type'        => 'integer','null'        => false,
                                            'default'     => '0',      'increment'   => true,
                                            'primary_key' => true),
                        'name' => array('type'        => 'varchar','size'        => 255,
                                            'null'        => false,    'default'     => ''));
        $query = xarDBCreateTable($tables['security_realms'],$fields);
        $dbconn->Execute($query);


        /*********************************************************************
         * CREATE TABLE xar_privileges (
         *   id int(11) NOT NULL auto_increment,
         *   name varchar(100) NOT NULL default '',
         *   realm varchar(100) NOT NULL default '',
         *   module_id int(11) NOT NULL default '0',
         *   component varchar(100) NOT NULL default '',
         *   instance varchar(100) NOT NULL default '',
         *   level int(11) NOT NULL default '0',
         *   description varchar(255) NOT NULL default '',
         *   PRIMARY KEY  (id)
         * )
         *********************************************************************/

        $fields = array('id'   => array('type' => 'integer', 'null' => false, 'default' => '0','increment' => true, 'primary_key' => true),
                        'name'  => array('type' => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'realm_id'=>array('type' => 'integer', 'null' => true, 'default' => null),
                        'module_id'=>array('type' => 'integer', 'null' => true, 'default' => null),
                        'component' => array('type'  => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'instance' => array('type'   => 'varchar', 'size' => 100, 'null' => false, 'default' => ''),
                        'level' => array('type'      => 'integer', 'null' => false,'default' => '0'),
                        'description' => array('type'=> 'varchar', 'size' => 255, 'null' => false, 'default'     => ''),
                        'type' => array('type'=> 'integer', 'null' => false, 'default'     => '0'));
        $query = xarDBCreateTable($tables['privileges'],$fields);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_privileges_name',
                       'fields'    => array('name', 'module_id', 'type'),
                       'unique'    => true);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_privileges_realm_id',
                       'fields'    => array('realm_id'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_privileges_module',
                       'fields'    => array('module_id'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_privileges_level',
                       'fields'    => array('level'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['privileges'],$index);
        $dbconn->Execute($query);

        xarDB::importTables(array('privileges' => $prefix . '_privileges'));

        /*********************************************************************
         * CREATE TABLE xar_privmembers (
         *   privilege_id integer unsigned NOT NULL default '0',
         *   parent_id integer unsigned NOT NULL default '0',
         *   PRIMARY KEY (privilege_id,parent_id)
         * )
         *********************************************************************/

        $query = "CREATE TABLE " . $tables['privmembers'] . "(
          `privilege_id` INTEGER UNSIGNED NOT NULL default '0',
          `parent_id`    INTEGER UNSIGNED NOT NULL default '0',
          PRIMARY KEY  (`privilege_id`,`parent_id`),
          KEY `i_xar_privmembers_pid` (`privilege_id`),
          KEY `i_xar_privmembers_parent_id` (`parent_id`)
        )";

        $dbconn->Execute($query);

        /*********************************************************************
         * CREATE TABLE xar_security_acl (
         *   partmember int(11) NOT NULL default '0',
         *   permmember int(11) NOT NULL default '0',
         *   KEY id (id,parentid)
         * )
         *********************************************************************/

        $query = xarDBCreateTable($tables['security_acl'],
                                  array('role_id'       => array('type'  => 'integer',
                                                                    'null'        => false,
                                                                    'default'     => '0',
                                                                    'primary_key'         => true),
                                        'privilege_id'      => array('type'   => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0',
                                                                   'primary_key'         => true)));
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_security_acl_role_id',
                       'fields'    => array('role_id'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_acl'],$index);
        $dbconn->Execute($query);

        $index = array('name'      => 'i_'.$prefix.'_security_acl_privilege_id',
                       'fields'    => array('privilege_id'),
                       'unique'    => false);
        $query = xarDBCreateIndex($tables['security_acl'],$index);
        $dbconn->Execute($query);

        xarDB::importTables(array('security_acl' => $prefix . '_security_acl'));

        /*********************************************************************
         * CREATE TABLE xar_security_instances (
         *   id int(11) NOT NULL default '0',
         *   name varchar(100) NOT NULL default '',
         *   module varchar(100) NOT NULL default '',
         *   type varchar(100) NOT NULL default '',
         *   instancevaluefield1 varchar(100) NOT NULL default '',
         *   instancedisplayfield1 varchar(100) NOT NULL default '',
         *   instanceapplication int(11) NOT NULL default '0',
         *   instancevaluefield2 varchar(100) NOT NULL default '',
         *   instancedisplayfield2 varchar(100) NOT NULL default '',
         *   description varchar(255) NOT NULL default '',
         *   PRIMARY KEY  (sid)
         * )
         *********************************************************************/

        $query = xarDBCreateTable($tables['security_instances'],
                                  array('id'  => array('type'       => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0',
                                                            'increment'   => true,
                                                            'primary_key' => true),
                                        'module_id' => array('type'     => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                        'component' => array('type'   => 'varchar',
                                                                 'size'        => 100,
                                                                 'null'        => false,
                                                                 'default'     => ''),
                                        'header' => array('type'   => 'varchar',
                                                              'size'        => 255,
                                                              'null'        => false,
                                                              'default'     => ''),
                                        'query' => array('type'   => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                        'ddlimit' => array('type'  => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                        'propagate' => array('type'  => 'integer',
                                                                 'null'        => false,
                                                                 'default'     => '0'),
                                        'instancechildid' => array('type'   => 'varchar',
                                                                       'size'        => 100,
                                                                       'null'        => false,
                                                                       'default'     => ''),
                                        'instanceparentid' => array('type'   => 'varchar',
                                                                        'size'        => 100,
                                                                        'null'        => false,
                                                                        'default'     => ''),
                                        'description' => array('type'=> 'varchar',
                                                                   'size'        => 255,
                                                                   'null'        => false,
                                                                   'default'     => '')));

        $dbconn->Execute($query);

        xarDB::importTables(array('security_instances' => $prefix . '_security_instances'));

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
    xarModVars::set('privileges', 'showrealms', false);
    xarModVars::set('privileges', 'inheritdeny', true);
    xarModVars::set('privileges', 'tester', 0);
    xarModVars::set('privileges', 'test', false);
    xarModVars::set('privileges', 'testdeny', false);
    xarModVars::set('privileges', 'testmask', 'All');
    xarModVars::set('privileges', 'realmvalue', 'none');
    xarModVars::set('privileges', 'realmcomparison','exact');
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
    $dbconn =& xarDB::getConn();
    $tables =& xarDB::getTables();

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
