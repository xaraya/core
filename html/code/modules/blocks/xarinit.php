<?php
/**
 * Initialise the blocks module
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * initialise the blocks module
 * @author Jim McDonald
 * @author Paul Rosania
 */
sys::import('xaraya.tableddl');
function blocks_init()
{

    // Get database information
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $prefix = xarDB::getPrefix();
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    
    $types_table = $tables['block_types'];
    $instances_table = $tables['block_instances'];    

    try {
        $dbconn->begin();
        
        // block types
        
        $fields = array(
            'id' => array(
                'type' => 'integer', 
                'unsigned' => true, 
                'null' => false, 
                'increment' => true,
                'primary_key' => true,
            ),        
            'module_id' => array(
                'type' => 'integer',
                'unsigned' => true,
                'null' => true,
            ),
            'state' => array(
                'type' => 'integer',
                'size' => 'tiny',
                'unsigned' => true,
                'null' => false,
                'default' => 1,  //xarBlock::TYPE_UNINITIALISED,
            ),
            'type' => array(
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ),
            'category' => array(
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ),
            'info' => array(
                'type' => 'text',
                'null' => true,
                'charset' => $charset,
            ),
        );
        $query = xarDBCreateTable($types_table, $fields); 
        $dbconn->Execute($query);

        $index = array(
            'name' => 'i_' . $types_table . '_types',
            'fields' => array('type', 'module_id', 'state'),
            'unique' => true,
        );
        $query = xarDBCreateIndex($types_table, $index);
        $dbconn->Execute($query);

        $index = array(
            'name' => 'i_' . $types_table . '_category',
            'fields' => array('category'),
            'unique' => false,
        );
        $query = xarDBCreateIndex($types_table, $index);
        $dbconn->Execute($query);
        
        // block instances

        $fields = array(
            'id' => array(
                'type' => 'integer', 
                'unsigned' => true, 
                'null' => false, 
                'increment' => true,
                'primary_key' => true,
            ),
            'type_id' => array(
                'type' => 'integer',
                'unsigned' => true,
                'null' => false,
            ),
            'name' => array(
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ),
            'title' => array(
                'type' => 'varchar',
                'size' => 254,
                'null' => true,
                'default' => null,
                'charset' => $charset,
            ),
            'content' => array(
                'type' => 'text',
                'null' => true,
                'charset' => $charset,
            ),
            'state' => array(
                'type' => 'integer',
                'size' => 'tiny',
                'unsigned' => true,
                'null' => false,
                'default' => xarBlock::BLOCK_STATE_VISIBLE,
            ),
        );
        $query = xarDBCreateTable($instances_table, $fields); 
        $dbconn->Execute($query);
        
        $index = array(
            'name' => 'i_' . $instances_table . '_instances',
            'fields' => array('name', 'state'),
            'unique' => true,
        );
        $query = xarDBCreateIndex($instances_table, $index);
        $dbconn->Execute($query);

        $index = array(
            'name' => 'i_' . $instances_table . '_type_id',
            'fields' => array('type_id'),
            'unique' => false,
        );
        $query = xarDBCreateIndex($instances_table, $index);
        $dbconn->Execute($query);
        
        // block cache (todo)

        $dbconn->commit();        
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    xarModVars::set('blocks', 'selstyle', 'plain');
    xarModVars::set('blocks', 'noexceptions', 1);

    // checkme: <chris/> The following note seems like a 1x thing
    /* There are old block instances defined previously in privs xarsetup.php file and used in the Block module.
       From this version we are adding management of security for blocks to Blocks module
       Old functionality in modules still exists.
       Note that the old instances and masks and code in the files was not 'matched' so don't think they worked properly in any case.
    */
    // checkme: <chris/> at install, surely we have nothing to remove?
    //xarRemoveInstances('blocks');
    //$blockGroupsTable    = $prefix . '_block_groups';
    $blockTypesTable     = $prefix . '_block_types';
    $blockInstancesTable = $prefix . '_block_instances';

    // checkme: are these necessary now we have anon privs per block instance?
    // todo: if we do keep them the query definitions need to be assessed 
    //The block instances differ and now defined on name (not title)
    //These need to be upgraded <chris/> is this upgrade a 1x thing?
    $query1 = "SELECT DISTINCT module_id FROM $blockTypesTable ";
    $query2 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
    $instances = array(array('header' => 'Module Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Name:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('blocks','Block',$instances);

    //Define an instance that refers to items that a block contains
    // checkme: items that a block contains? what does that mean? 
    $query1 = "SELECT DISTINCT instances.name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.id = instances.type_id";
    $modulesTable = $prefix . '_modules';
    $query2 = "SELECT DISTINCT name FROM $modulesTable ";
    $instances = array(array('header' => 'Block Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Module Name:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('blocks','BlockItem',$instances);

    xarRegisterMask('ViewBlocks','All','blocks','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditBlocks','All','blocks','All','All','ACCESS_EDIT');
    xarRegisterMask('AddBlocks','All','blocks','All','All','ACCESS_ADD');
    xarRegisterMask('ManageBlocks','All','blocks','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminBlocks','All','blocks','All','All','ACCESS_ADMIN');

    // Installation complete; check for upgrades
    return blocks_upgrade('2.2.0');

}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @return boolean true on success, false on failure
 */
function blocks_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.2.0':
            // Register blocks module event observers 
            xarEvents::registerObserver('ModRemove', 'blocks');  
            xarEvents::registerObserver('ModActivate', 'blocks');
            xarEvents::registerObserver('ModDeactivate', 'blocks');         
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
function blocks_delete()
{
  //this module cannot be removed
  return false;
}

?>