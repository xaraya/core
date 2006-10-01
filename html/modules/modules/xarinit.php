<?php
/**
 * Module initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the modules module
 *
 * @param none $
 * @returns bool
 * @throws DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables['modules'] = $systemPrefix . '_modules';
    $tables['module_vars'] = $sitePrefix . '_module_vars';
    $tables['module_itemvars'] = $sitePrefix . '_module_itemvars';
    $tables['hooks'] = $sitePrefix . '_hooks';
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
         *   xar_id int(11) NOT NULL auto_increment,
         *   xar_name varchar(64) NOT NULL default '',
         *   xar_regid int(10) unsigned NOT NULL default '0',
         *   xar_directory varchar(64) NOT NULL default '',
         *   xar_version varchar(10) NOT NULL default '0',
         *   xar_mode int(6) NOT NULL default '1',
         *   xar_class varchar(64) NOT NULL default '',
         *   xar_category varchar(64) NOT NULL default '',
         *   xar_admin_capable tinyint(1) NOT NULL default '0',
         *   xar_user_capable tinyint(1) NOT NULL default '0',
         *   xar_state tinyint(1) NOT NULL default '0'
         *   PRIMARY KEY  (xar_id)
         * )
         */
        $fields = array(
                        'xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
                        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_regid' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
                        'xar_directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
                        'xar_mode' => array('type' => 'integer', 'size' => 'small', 'null' => false, 'default' => '1'),
                        'xar_class' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_category' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_admin_capable' => array('type' => 'integer', 'size' => 'tiny', 'null' => false, 'default' => '0'),
                        'xar_user_capable' => array('type' => 'integer', 'size' => 'tiny', 'null' => false, 'default' => '0'),
                        'xar_state' => array('type' => 'integer', 'null' => false, 'default' => '1')
                        );

        // Create the modules table
        $query = xarDBCreateTable($tables['modules'], $fields);
        $dbconn->Execute($query);

        $modInfo = xarMod_getFileInfo('modules');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        // Manually Insert Modules module into modules table
        $seqId = $dbconn->GenId($tables['modules']);
        $query = "INSERT INTO " . $tables['modules'] . "
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode,
               xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $bindvars = array($seqId,'modules',1,'modules',(string) $modVersion,1,'Core Admin','Global',1,0,3);
        $dbconn->Execute($query,$bindvars);

        // Save the actual insert id
        $savedmodid = $dbconn->PO_Insert_ID($tables['modules'], 'xar_id');


        /** Module vars table is created earlier now (base mod, where config_vars table was created */

        /**
         * CREATE TABLE xar_module_itemvars (
         *   xar_mvid int(11) NOT NULL auto_increment,
         *   xar_itemid  int(11) NOT NULL default 0,
         *   xar_value longtext,
         *   PRIMARY KEY  (xar_mvid, xar_itemid)
         * )
         */
        $fields = array(
                        'xar_mvid' => array('type' => 'integer', 'null' => false, 'primary_key' => true),
                        'xar_itemid' => array('type' => 'integer', 'null' => false, 'unsigned' => true, 'primary_key' => true),
                        'xar_value' => array('type' => 'text', 'size' => 'long')
                        );

        // Create the module itemvars table
        $query = xarDBCreateTable($tables['module_itemvars'], $fields);
        $dbconn->Execute($query);

        // MrB: do we want an index on xar_value, on large sites, lots of records may exist
        // <mikespub> the only reason why you might want to use an index on value is when you're doing
        // simple queries or stats based on it. But since all values of all kinds of stuff
        // are mixed together here, and we're not querying by value anyway, this wouldn't help at all...
        // Pro: searching for values will speed up (is that used somewhere)
        // Con: setting a user mod var will become slower and slower (relatively tho)
        // prefix_hooks
        /**
         * CREATE TABLE xar_hooks (
         *   xar_id int(10) unsigned NOT NULL auto_increment,
         *   xar_object varchar(64) NOT NULL default '',
         *   xar_action varchar(64) NOT NULL default '',
         *   xar_smodule varchar(64) NOT NULL default '',
         *   xar_stype varchar(64) NOT NULL default '',
         *   xar_tarea varchar(64) NOT NULL default '',
         *   xar_tmodule varchar(64) NOT NULL default '',
         *   xar_ttype varchar(64) NOT NULL default '',
         *   xar_tfunc varchar(64) NOT NULL default '',
         *   PRIMARY KEY  (xar_id)
         * )
         */
        $fields = array(
                        'xar_id'      => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
                        'xar_object'  => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_action'  => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_smodid'  => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        // TODO: switch to integer for itemtype (see also xarMod.php)
                        'xar_stype'   => array('type' => 'varchar', 'size' => 64, 'null' => false, 'default' => ''),
                        'xar_tarea'   => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_tmodid'  => array('type' => 'integer', 'null' => false, 'default' => '0'),
                        'xar_ttype'   => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_tfunc'   => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'xar_order'   => array('type' => 'integer', 'null' => false, 'default' => '0')
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

        $sql = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
                VALUES (?,?,?,?)";
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
            $id = $dbconn->GenId($tables['module_vars']);
            array_unshift($modvar,$id);
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
    $selstyle = xarModGetVar('modules', 'hidecore');
    $selstyle = xarModGetVar('modules', 'selstyle');
    $selstyle = xarModGetVar('modules', 'selfilter');
    $selstyle = xarModGetVar('modules', 'selsort');
    if (empty($hidecore)) xarModSetVar('modules', 'hidecore', 0);
    if (empty($selstyle)) xarModSetVar('modules', 'selstyle', 'plain');
    if (empty($selfilter)) xarModSetVar('modules', 'selfilter', XARMOD_STATE_ANY);
    if (empty($selsort)) xarModSetVar('modules', 'selsort', 'nameasc');



    // New in 1.1.x series but not used
    xarModSetVar('modules', 'disableoverview',0);

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
 * @param none $
 * @returns bool
 */
function modules_delete()
{
    // this module cannot be removed
    return false;
}

?>
