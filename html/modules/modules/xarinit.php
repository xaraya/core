<?php
/**
 * Module initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @link http://xaraya.com/index.php/release/1.html
 */
// Load Table Maintainance API
sys::import('xaraya.xarTableDDL');
/**
 * Initialise the modules module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    $tables['modules'] = $prefix . '_modules';
    $tables['module_vars'] = $prefix . '_module_vars';
    $tables['module_itemvars'] = $prefix . '_module_itemvars';
    $tables['hooks'] = $prefix . '_hooks';
    // Create tables
    // This should either go, or fail competely
    try {
        $dbconn->begin();
        /**
         * Here we create all the tables for the module system
         *
         * prefix_modules       - basic module info
         * prefix_module_vars   - module variables table
         * prefix_hooks         - table for hooks
         */
        // prefix_modules
        /**
         * CREATE TABLE xar_modules (
         *   id int(11) NOT NULL auto_increment,
         *   name varchar(64) NOT NULL default '',
         *   regid int(10) INTEGER NOT NULL default '0',
         *   directory varchar(64) NOT NULL default '',
         *   version varchar(10) NOT NULL default '0',
         *   class varchar(64) NOT NULL default '',
         *   category varchar(64) NOT NULL default '',
         *   admin_capable INTEGER NOT NULL default '0',
         *   user_capable INTEGER NOT NULL default '0',
         *   state INTEGER NOT NULL default '0'
         *   PRIMARY KEY  (id)
         * )
         */
        $fields = array(
                        'id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
                        'name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'regid' => array('type' => 'integer', 'default' => null),
                        'directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
                        'class' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'category' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'admin_capable' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'user_capable' => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'state' => array('type' => 'integer', 'null' => false, 'default' => '1')
                        );

        // Create the modules table
        $query = xarDBCreateTable($tables['modules'], $fields);
        $dbconn->Execute($query);

        $modInfo = xarMod_getFileInfo('modules');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        // Manually Insert Modules module into modules table
        $query = "INSERT INTO " . $tables['modules'] . "
              (name, regid, directory, version,
               class, category, admin_capable, user_capable, state )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $bindvars = array('modules',1,'modules',(string) $modVersion,'Core Admin','Global',1,0,3);
        $dbconn->Execute($query,$bindvars);

        // Save the actual insert id
        $savedmodid = $dbconn->getLastId($tables['modules']);


        /** Module vars table is created earlier now (base mod, where config_vars table was created */

        /**
         * CREATE TABLE module_itemvars (
         *   module_var_id    integer NOT NULL auto_increment,
         *   item_id          integer NOT NULL default 0,
         *   value            longtext,
         *   PRIMARY KEY      (module_var_id, item_id)
         * )
         */
        $fields = array(
                        'module_var_id' => array('type' => 'integer', 'null' => false, 'primary_key' => true),
                        'item_id' => array('type' => 'integer', 'null' => false, 'unsigned' => true, 'primary_key' => true),
                        'value' => array('type' => 'text', 'size' => 'long')
                        );

        // Create the module itemvars table
        $query = xarDBCreateTable($tables['module_itemvars'], $fields);
        $dbconn->Execute($query);

        /**
         * CREATE TABLE xar_hooks (
         *   id         integer NOT NULL auto_increment,
         *   object     varchar(64) NOT NULL default '',
         *   action     varchar(64) NOT NULL default '',
         *   s_module_id integer default null,
         *   s_type      varchar(64) NOT NULL default '',
         *   t_area      varchar(64) NOT NULL default '',
         *   t_module_id integer not null,
         *   t_type      varchar(64) NOT NULL default '',
         *   t_func      varchar(64) NOT NULL default '',
         *   priority    integer default 0
         *   PRIMARY KEY (id)
         * )
         */
        $fields = array(
                        'id'          => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
                        'object'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'action'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        's_module_id' => array('type' => 'integer', 'null' => true, 'default' => null),
                        // TODO: switch to integer for itemtype (see also xarMod.php)
                        's_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false, 'default' => ''),
                        't_area'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        't_module_id'  => array('type' => 'integer', 'null' => false),
                        't_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        't_func'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'priority'       => array('type' => 'integer', 'null' => false, 'default' => '0')
                    );
        // TODO: no indexes?

        // Create the hooks table
        $query = xarDBCreateTable($tables['hooks'], $fields);
        $dbconn->Execute($query);

        // <andyv> Add module variables for default user/admin, used in modules list
        /**
         * at this stage of installer mod vars cannot be set, so we use DB calls
         * prolly need to move this closer to installer, not sure yet
         */

        $sql = "INSERT INTO " . $tables['module_vars'] . " (module_id, name, value)
                VALUES (?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $modvars = array(
                         // default show-hide core modules
                         array($savedmodid,'hidecore','0'),
                         // default regenerate command
                         array($savedmodid,'regen','0'),
                         // default style of module list
                         array($savedmodid,'selstyle','plain'),
                         // default filtering based on module states
                         array($savedmodid,'selfilter', '0'),
                         // default modules list sorting order
                         array($savedmodid,'selsort','nameasc'),
                         // default show-hide modules statistics
                         array($savedmodid,'hidestats','0'),
                         // default maximum number of modules listed per page
                         array($savedmodid,'selmax','all'),
                         // default start page
                         array($savedmodid,'startpage','overview'),
                         // disable overviews
                         array($savedmodid,'disableoverview',0),
                         // expertlist
                         array($savedmodid,'expertlist','0'));

        foreach($modvars as &$modvar) {
            $stmt->executeUpdate($modvar);
        }

        // We're done, thanks, commit the thingie
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        throw $e;
    }

    // Initialisation successful
    return true;
}

/**
 * Activates the modules module
 *
 * @param none $
 * @returns bool
 */
function modules_activate()
{
    // make sure we dont miss empty variables (which were not passed thru)
    $selstyle = xarModVars::get('modules', 'hidecore');
    $selstyle = xarModVars::get('modules', 'selstyle');
    $selstyle = xarModVars::get('modules', 'selfilter');
    $selstyle = xarModVars::get('modules', 'selsort');
    if (empty($hidecore)) xarModVars::set('modules', 'hidecore', 0);
    if (empty($selstyle)) xarModVars::set('modules', 'selstyle', 'plain');
    if (empty($selfilter)) xarModVars::set('modules', 'selfilter', XARMOD_STATE_ANY);
    if (empty($selsort)) xarModVars::set('modules', 'selsort', 'nameasc');



    // New in 1.1.x series but not used
    xarModVars::set('modules', 'disableoverview',0);

    return true;
}

/**
 * Upgrade the modules module from an old version
 *
 * @param oldversion $ the old version to upgrade from
 * @returns bool
 * @todo include setting moduservars in next upgrade (2.1)
 */
function modules_upgrade($oldVersion)
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($oldVersion) {
    case '2.3.0':
        // 1.0 version, add upgrade code to 2.x here
        // - hooks: removed columns smodule, tmodule in xar_hooks, made them smodid and tmodid
        // - module states: table removed
    case '2.4.0':
        //current version
    }
    return true;
}

/**
 * Delete the modules module
 *
 * @returns bool
 */
function modules_delete()
{
    // this module cannot be removed
    return false;
}

?>
