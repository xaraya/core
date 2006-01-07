<?php
/**
 * Administration System
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
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
    $dbconn =& xarDBGetConn();
    $table =& xarDBGetTables();

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
    $sql  = "INSERT INTO $adminMenuTable (xar_amid, xar_name, xar_category, xar_weight, xar_flag) VALUES (?,?,?,?,?)";
    $stmt = $dbconn->prepareStatement($sql);
    
    $coremods = array (
        array('adminpanels', 'Global',0,1),
        array('mail'       , 'Global',0,1),
        array('dynamicdata', 'Content',0,1),
        array('themes'     , 'Global',0,1),
        array('authsystem' , 'Global',0,1),
        array('base'       , 'Global',0,1),
        array('blocks'     , 'Global',0,1),
        array('modules'    , 'Global',0,1),
        array('privileges' , 'Users & Groups',0,1),
        array('roles'      , 'Users & Groups',0,1)
    );
        
    try {
        $dbconn->begin();
        foreach($coremods as &$bindvars) {
            $id = $dbconn->GenId($adminMenuTable);
            array_unshift($bindvars,$id);
            $result = $stmt->executeUpdate($bindvars);
        }
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    
    // Register blocks
    if (!xarModAPIFunc('blocks','admin','register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'adminmenu'))) return;

    if (!xarModAPIFunc('blocks', 'admin', 'register_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> 'waitingcontent'))) return;


    // Set module variables
    xarModSetVar('adminpanels','menuposition', 'l');
    xarModSetVar('adminpanels','menustyle', 'bycat');
    xarModSetVar('adminpanels','showontop', 1);
    xarModSetVar('adminpanels','showhelp', 1);
    xarModSetVar('adminpanels','marker', '[x]');
    
    // after version 1.2.0
    xarModSetVar('adminpanels','showlogout', 1);
    xarModSetVar('adminpanels','showmarker', 0);
    
    // Initialisation successful
    return true;
}

/**
 * Upgrade the adminpanels module from an old version
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   $oldversion
 * @return  true on success or false on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_upgrade($oldversion)
{
        
    // Upgrade dependent on old version number
    switch($oldversion) {
        case '1.0': // first ever version as string
        case  1.0:  // first ever version as float
        case '1.2.0':
            // sort out modvars, remove unused and add new ones
            if(!xarModGetVar('adminpanels','showlogout')){
                xarModSetVar('adminpanels','showlogout', 1);
            }
            if(xarModGetVar('adminpanels','showold')){
                xarModDelVar('adminpanels','showold');
                xarModSetVar('adminpanels','showmarker', 0);
            }
        case '1.2.1':
            // Remove redundant modvars.
            xarModDelVar('adminpanels', 'showontop');
            xarModDelVar('adminpanels', 'menuposition');
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
  //this module cannot be removed via gui
  return false;
}

?>
