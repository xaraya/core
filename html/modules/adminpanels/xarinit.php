<?php
/**
 * File: $Id$
 *
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
*/

// Load Table Maintaince API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the adminpanels module
 *
 * @author  Andy Varganov <andyv@yaraya.com>
 * @access  public
 * @param   none
 * @return  true on success or void or false on failure
 * @throws  'DATABASE_ERROR'
 * @todo    nothing
*/
function adminpanels_init()
{
    // Get database information
    list($dbconn) = xarDBGetConn();
    $table = xarDBGetTables();

    // Create tables
    $adminMenuTable = xarDBGetSiteTablePrefix() . '_admin_menu';
    /*********************************************************************
     * Here we create all the tables for the adminpanels module
     *
     * prefix_admin_menu       - admin modules
     ********************************************************************/

    // prefix_admin_menu
    /*********************************************************************
    * CREATE TABLE xar_admin_menu (
    *  xar_amid int(11) NOT NULL auto_increment,
    *  xar_name varchar(32) NOT NULL default '',
    *  xar_category varchar(32) NOT NULL default '',
    *  xar_weight int(11) NOT NULL default '0',
    *  xar_flag tinyint(4) NOT NULL default '1',
    *  PRIMARY KEY  (xar_amid)
    * )
    *********************************************************************/
    // *_admin_menu
    $query = xarDBCreateTable($adminMenuTable,
                             array('xar_amid'        => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0',
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_name'        => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_category'    => array('type'        => 'varchar',
                                                             'size'        => 32,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_weight'       => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_flag'         => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '1')));
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    
    // Set config vars

    // Fill admin menu
    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'adminpanels', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'authsystem', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'base', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'blocks', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'groups', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'modules', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'permissions', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'users', 'Users & Groups', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Register Block types
    $res = xarBlockTypeRegister('adminpanels', 'adminmenu');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }

    // Register Block types
    $res = xarBlockTypeRegister('adminpanels', 'waitingcontent');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return;
    }
    
    // Set module variables
    xarModSetVar('adminpanels','showold', 1);
    xarModSetVar('adminpanels','menuposition', 'l');
    xarModSetVar('adminpanels','menustyle', 'bycat');
    xarModSetVar('adminpanels','showontop', 1);
    xarModSetVar('adminpanels','showhelp', 1);
    
    /* Create the table and hooks for the waiting content block */

    // Create tables
    $xartable['waitingcontent'] = xarDBGetSiteTablePrefix() . '_admin_wc';
    // Create tables
    $query = xarDBCreateTable($xartable['waitingcontent'],
                             array('xar_wcid'        => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0',
                                                            'increment'   => true,
                                                            'primary_key' => true),
                                   'xar_moduleid'    => array('type'        => 'integer',
                                                            'unsigned'    => true,
                                                            'null'        => false,
                                                            'default'     => '0'),
                                   'xar_hits'          => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'size'        => 'big',
                                                            'default'     => '0')));

    $result =& $dbconn->Execute($query);
    //if (!$result) return;

    $query = xarDBCreateIndex($xartable['waitingcontent'],
                             array('name'   => 'xar_moduleid',
                                   'fields' => array('xar_moduleid'),
                                   'unique' => false));

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($xartable['waitingcontent'],
                             array('name'   => 'xar_hits',
                                   'fields' => array('xar_hits'),
                                   'unique' => false));

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set up module hooks

    // when a module item is displayed
    // (use xarVarSetCached('Hooks.hitcount','save', 1) to tell hitcount *not*
    // to display the hit count, but to save it in 'Hooks.hitcount', 'value')
    if (!xarModRegisterHook('item', 'display', 'GUI',
                           'waitingcontent', 'user', 'display')) {
        return false;
    }
    // when a module item is created (set extrainfo to the module name ?)
    if (!xarModRegisterHook('item', 'create', 'API',
                           'waitingcontent', 'admin', 'create')) {
        return false;
    }
    // when a module item is deleted (set extrainfo to the module name ?)
    if (!xarModRegisterHook('item', 'delete', 'API',
                           'waitingcontent', 'admin', 'delete')) {
        return false;
    }
    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarModRegisterHook('module', 'remove', 'API',
                           'waitingcontent', 'admin', 'deleteall')) {
        return false;
    }

    // Initialisation successful
    return true;
}

/**
 * Upgrade the adminpanels module from an old version
 *
 * @author  Andy Varganov <andyv@yaraya.com>
 * @access  public
 * @param   $oldVersion
 * @return  true on success or false on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_upgrade($oldVersion)
{
    return false;
}

/**
 * Delete the adminpanels module
 *
 * @author  Andy Varganov <andyv@yaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or false on failure
 * @todo    nothing
*/

function adminpanels_delete()
{
    return false;
}

?>