<?php

/**
 * Initialisation functions for the security module
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */
sys::import('xaraya.tableddl');

/**
 * Initialise the privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @return boolean true on success, false on failure
 * @throws DATABASE_ERROR
 */
function privileges_init()
{
    $dbconn = xarDB::getConn();
    $tables = & xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['privileges'] = $prefix . '_privileges';
    $tables['privmembers'] = $prefix . '_privmembers';
    $tables['security_acl'] = $prefix . '_security_acl';
    $tables['security_instances'] = $prefix . '_security_instances';
    $tables['security_realms'] = $prefix . '_security_realms';
    $tables['security_privsets'] = $prefix . '_security_privsets';

    // All or nothing
    try
    {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();

        // Create tables
        /*         * *******************************************************************
         * Here we create all the tables for the privileges module
         *
         * prefix_privileges       - holds privileges info
         * prefix_privmembers      - holds info on privileges group membership
         * prefix_security_acl     - holds info on privileges assignments to roles
         * prefix_security_masks   - holds info on masks for security checks
         * prefix_security_instances       - holds module instance definitions
         * prefix_security_realms  - holds realsm info
         * ****************************************************************** */

        /*         * *******************************************************************
         * CREATE TABLE xar_security_realms (
         *  id int(11) NOT NULL auto_increment,
         *  name varchar(254) NOT NULL,
         *  PRIMARY KEY  (id)
         * )
         * ******************************************************************* */
        $fields = array('id' => array('type' => 'integer', 'null' => false,
                'unsigned' => true, 'increment' => true,
                'primary_key' => true),
            'name' => array('type' => 'varchar', 'size' => 254,
                'null' => false,
                'charset' => $charset));
        $query = xarDBCreateTable($tables['security_realms'], $fields);
        $dbconn->Execute($query);


        /*         * *******************************************************************
         * CREATE TABLE xar_privileges (
         *   id int(11) NOT NULL auto_increment,
         *   name varchar(100) NOT NULL,
         *   realm_id integer unsigned default NULL,
         *   module_id integer unsigned NOT NULL,
         *   component varchar(100) NOT NULL,
         *   instance varchar(100) NOT NULL,
         *   level int(11) NOT NULL default '0',
         *   description varchar(254) NOT NULL,
         *   itemtype integer unsigned NOT NULL,
         *   PRIMARY KEY  (id)
         * )
         * ******************************************************************* */

        $fields = array(
            'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
            'name' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
            'realm_id' => array('type' => 'integer', 'unsigned' => true, 'null' => true),
            'module_id' => array('type' => 'integer', 'unsigned' => true, 'null' => true),
            'component' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'charset' => $charset),
            'instance' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset),
            'level' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
            'description' => array('type' => 'varchar', 'size' => 254, 'null' => false, 'charset' => $charset),
            'itemtype' => array('type' => 'integer', 'unsigned' => true, 'null' => false));
        $query = xarDBCreateTable($tables['privileges'], $fields);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privileges_name',
            'fields' => array('name', 'module_id', 'itemtype'),
            'unique' => true);
        $query = xarDBCreateIndex($tables['privileges'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privileges_realm_id',
            'fields' => array('realm_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['privileges'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privileges_module',
            'fields' => array('module_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['privileges'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privileges_level',
            'fields' => array('level'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['privileges'], $index);
        $dbconn->Execute($query);

        xarDB::importTables(array('privileges' => $prefix . '_privileges'));

        /*         * *******************************************************************
         * CREATE TABLE xar_privmembers (
         *   privilege_id integer unsigned NOT NULL default '0',
         *   parent_id integer unsigned NOT NULL default '0',
         *   PRIMARY KEY (privilege_id,parent_id)
         * )
         * ******************************************************************* */
        $fields = array(
            'privilege_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'primary_key' => true),
            'parent_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'primary_key' => true)
        );
        $query = xarDBCreateTable($tables['privmembers'], $fields);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privmembers_pid',
            'fields' => array('privilege_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['privmembers'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_privmembers_parent_id',
            'fields' => array('parent_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['privmembers'], $index);
        $dbconn->Execute($query);


        /*         * *******************************************************************
         * CREATE TABLE xar_security_acl (
         *   role_id integer NOT NULL,
         *   privilege_id integer NOT NULL,
         *   PRIMARY KEY (role_id,privilege_id)
         * )
         * ******************************************************************* */

        $query = xarDBCreateTable($tables['security_acl'], array('role_id' => array('type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'primary_key' => true),
            'privilege_id' => array('type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'primary_key' => true)));
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_security_acl_role_id',
            'fields' => array('role_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['security_acl'], $index);
        $dbconn->Execute($query);

        $index = array('name' => $prefix . '_security_acl_privilege_id',
            'fields' => array('privilege_id'),
            'unique' => false);
        $query = xarDBCreateIndex($tables['security_acl'], $index);
        $dbconn->Execute($query);

        xarDB::importTables(array('security_acl' => $prefix . '_security_acl'));

        /*         * *******************************************************************
         * CREATE TABLE xar_security_instances (
         *   id int(11) NOT NULL default '0',
         *   name varchar(100) NOT NULL,
         *   module varchar(100) NOT NULL,
         *   type varchar(100) NOT NULL,
         *   instancevaluefield1 varchar(100) NOT NULL,
         *   instancedisplayfield1 varchar(100) NOT NULL,
         *   instanceapplication int(11) NOT NULL default '0',
         *   instancevaluefield2 varchar(100) NOT NULL,
         *   instancedisplayfield2 varchar(100) NOT NULL,
         *   description varchar(254) NOT NULL,
         *   PRIMARY KEY  (sid)
         * )
         * ******************************************************************* */

        $query = xarDBCreateTable($tables['security_instances'], array('id' => array('type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'increment' => true,
                'primary_key' => true),
            'module_id' => array('type' => 'integer',
                'unsigned' => true,
                'null' => true),
            'component' => array('type' => 'varchar',
                'size' => 254,
                'null' => false,
                'charset' => $charset),
            'header' => array('type' => 'varchar',
                'size' => 254,
                'null' => false,
                'charset' => $charset),
            'query' => array('type' => 'varchar',
                'size' => 254,
                'null' => false,
                'charset' => $charset),
            'ddlimit' => array('type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'default' => '0'),
            'description' => array('type' => 'varchar',
                'size' => 254,
                'null' => false,
                'charset' => $charset)));

        $dbconn->Execute($query);

        xarDB::importTables(array('security_instances' => $prefix . '_security_instances'));

        $dbconn->commit();
        // Set up an initial value for module variables.
        // Initialisation successful
    } catch (Exception $e)
    {
        $dbconn->rollback();
        throw $e;
    }

    // Installation complete; check for upgrades
    return privileges_upgrade('2.0.0');
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
    xarModVars::set('privileges', 'realmcomparison', 'exact');
    xarModVars::set('privileges', 'exceptionredirect', false);
    xarModVars::set('privileges', 'maskbasedsecurity', false);
    xarModVars::set('privileges', 'clearcache', time());
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function privileges_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        default:
            break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return boolean
 */
function privileges_delete()
{
    // this module cannot be removed
    return false;
}

?>
