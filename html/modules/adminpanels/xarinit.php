<?php
/**
 * File: $Id: s.xarinit.php 1.22 03/01/26 20:03:00-05:00 John.Cox@mcnabb. $
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

/**
 * Initialise the adminpanels module
 *
 * @author  Andy Varganov <andyv@xaraya.com>
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

    // Load Table Maintaince API
    xarDBLoadTableMaintenanceAPI();

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
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'mail', 'Global', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'dynamicdata', 'Content', 0, 1);";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $id = $dbconn->GenId($adminMenuTable);
    $query = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES ($id, 'themes', 'Global', 0, 1);";
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

    // Register blocks
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'adminmenu'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'waitingcontent'))) return;


    // Set module variables
    xarModSetVar('adminpanels','showold', 1);
    xarModSetVar('adminpanels','menuposition', 'l');
    xarModSetVar('adminpanels','menustyle', 'bycat');
    xarModSetVar('adminpanels','showontop', 1);
    xarModSetVar('adminpanels','showhelp', 1);
    xarModSetVar('adminpanels','marker', '[x]');

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
                                   'xar_itemid'     => array('type'        => 'integer',
                                                            'unsigned'    => true,
                                                            'null'        => false,
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
                             array('name'   => 'xar_itemid',
                                   'fields' => array('xar_itemid'),
                                   'unique' => false));

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set up module hooks



    // when a module item is created
    if (!xarModRegisterHook('item', 'create', 'API',
                           'adminpanels', 'admin', 'createwc')) {
        return false;
    }

// Note : we use the same function in both update and delete here
//        => delete the waiting content entry (if it still exists)

    // when a module item is updated
    if (!xarModRegisterHook('item', 'update', 'API',
                           'adminpanels', 'admin', 'deletewc')) {
        return false;
    }

    // when a module item is deleted
    if (!xarModRegisterHook('item', 'delete', 'API',
                           'adminpanels', 'admin', 'deletewc')) {
        return false;
    }

    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarModRegisterHook('module', 'remove', 'API',
                           'adminpanels', 'admin', 'deleteallwc')) {
        return false;
    }

    // Initialisation successful
    return true;
}

/**
 * Upgrade the adminpanels module from an old version
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   $oldVersion
 * @return  true on success or false on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch($oldVersion) {
        case 1.0:
            // Code to upgrade from version 1.0 goes here
            break;
        // TODO : remove for release version
        case 2.0:
            // Code to upgrade from version 2.0 goes here
            break;
        case 2.1:
//            if (!xarModRegisterHook('item', 'search', 'GUI',
//                                   'articles', 'user', 'search')) {
//                return false;
//            }
            break;
        case 2.2:
            // Code to upgrade from version 2.2 goes here

            break;
        case 2.3:
            // Register BL tags
//            xarTplRegisterTag('articles', 'articles-field',
//                              //array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
//                              array(),
//                              'articles_userapi_handleFieldTag');
            break;
        case 2.4:
            // Code to upgrade from version 2.4 goes here

            break;
        case 2.5:
            // Code to upgrade from version 2.5 goes here

            break;
    }
    return true;
}

/**
 * Delete the adminpanels module
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or false on failure
 * @todo    restore the default behaviour prior to 1.0 release
*/
function adminpanels_delete()
{
  //this module cannot be removed
  return false;

    // temporary workaround to enable deactivate and upgrade
    // TODO: remove prior to xarays 1.0 release

    // removal of module stuff from version 1.0
    xarModDelVar('adminpanels', 'showold');
    xarModDelVar('adminpanels', 'menuposition');
    xarModDelVar('adminpanels', 'menustyle');
    xarModDelVar('adminpanels', 'showontop');
    xarModDelVar('adminpanels', 'showhelp');
    xarModDelVar('adminpanels', 'marker');

    // need to drop the module tables too
    // Get database information
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['admin_menu']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) return;

     // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['waiting_content']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // unregister our blocks.. maybe not
    // xarBlockTypeUnregister('adminpanels', 'adminmenu');
    // xarBlockTypeUnregister('articles', 'waitingcontent');

    // when a module item is created
    if (!xarModUnregisterHook('item', 'create', 'API',
                           'adminpanels', 'admin', 'createwc')) {
        return false;
    }

// Note : we use the same function in both update and delete here
//        => delete the waiting content entry (if it still exists)

    // when a module item is updated
    if (!xarModUnregisterHook('item', 'update', 'API',
                           'adminpanels', 'admin', 'deletewc')) {
        return false;
    }

    // when a module item is deleted
    if (!xarModUnregisterHook('item', 'delete', 'API',
                           'adminpanels', 'admin', 'deletewc')) {
        return false;
    }

    // when a whole module is removed, e.g. via the modules admin screen
    // (set object ID to the module name !)
    if (!xarModUnregisterHook('module', 'remove', 'API',
                           'adminpanels', 'admin', 'deleteallwc')) {
        return false;
    }


    // Remove Masks and Instances
    xarRemoveMasks('adminpanels');
    xarRemoveInstances('adminpanels');

    // we are done with removing stuff from version 1.0

    return true;
}

?>
