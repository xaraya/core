<?php
/**
 * Initialise the blocks module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * initialise the blocks module
 * @author Jim McDonald, Paul Rosania
 */
function blocks_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $prefix = xarDBGetSystemTablePrefix();

    // Create tables inside a transaction
    try {
        $dbconn->begin();
            
        // *_block_groups
        $query = xarDBCreateTable($prefix . '_block_groups',
                                  array('xar_id'         => array('type'        => 'integer',
                                                                  'null'        => false,
                                                                  'increment'   => true,
                                                                  'primary_key' => true),
                                        'xar_name'        => array('type'        => 'varchar',
                                                                   'size'        => 255,
                                                                   'null'        => false,
                                                                   'default'     => ''),
                                        'xar_template'    => array('type'        => 'varchar',
                                                                   'size'        => 255,
                                                                   'null'        => false,
                                                                   'default'     => '')));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_groups',
                                  array('name'   => 'i_' . $prefix . '_block_groups',
                                        'fields' => array('xar_name'),
                                        'unique' => 'true'));
        $dbconn->Execute($query);
        
        // *_block_instances
        $query = xarDBCreateTable($prefix . '_block_instances',
                                  array('xar_id'          => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'increment'   => true,
                                                                   'primary_key' => true),
                                        'xar_type_id'     => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0'),
                                        'xar_name'       => array('type'        => 'varchar',
                                                                  'size'        => 100,
                                                                  'null'        => false,
                                                                  'default'     => NULL),
                                        'xar_title'       => array('type'        => 'varchar',
                                                                   'size'        => 255,
                                                                   'null'        => true,
                                                                   'default'     => NULL),
                                        'xar_content'     => array('type'        => 'text',
                                                                   'null'        => false),
                                        'xar_template'    => array('type'        => 'varchar',
                                                                   'size'        => 255,
                                                                   'null'        => true,
                                                                   'default'     => NULL),
                                        'xar_state'       => array('type'        => 'integer',
                                                                   'size'        => 'tiny',
                                                                   'null'        => false,
                                                                   'default'     => '2'),
                                        'xar_refresh'     => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0'),
                                        'xar_last_update' => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0')));
        
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_instances',
                                  array('name'   => 'i_' . $prefix . '_block_instances',
                                        'fields' => array('xar_type_id'),
                                        'unique' => false));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_instances',
                                  array('name'   => 'i_' . $prefix . '_block_instances_u2',
                                        'fields' => array('xar_name'),
                                        'unique' => true));
        $dbconn->Execute($query);
        
        // *_block_types
        $query = xarDBCreateTable($prefix . '_block_types',
                                  array(
                                        'xar_id' => array(
                                                          'type'          => 'integer',
                                                          'null'          => false,
                                                          'increment'     => true,
                                                          'primary_key'   => true
                                                          ),
                                        'xar_type' => array(
                                                            'type'          => 'varchar',
                                                            'size'          => 64,
                                                            'null'          => false,
                                                            'default'       => ''
                                                            ),
                                        'xar_modid' => array(
                                                              'type'          => 'integer',
                                                              'unsigned'      => true,
                                                              'null'          => false,
                                                              'default'       => '0'
                                                              ),
                                        'xar_info' => array(
                                                            'type'          => 'text',
                                                            'null'          => true
                                                            )
                                        )
                                  );
        
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_types',
                                  array('name'   => 'i_' . $prefix . '_block_types2',
                                        'fields' => array('xar_modid', 'xar_type'),
                                        'unique' => 'false'));
        $dbconn->Execute($query);
        /*
         TODO: Find a fix for this - Postgres will not allow partial indexes
         $query = xarDBCreateIndex($prefix . '_block_types',
         array('name'   => 'i_' . $prefix . '_block_types_2',
         'fields' => array('xar_type(50)', 'xar_modid(50)'),
         'unique' => true));
         $result =& $dbconn->Execute($query);
        */
        // *_block_group_instances
        $query = xarDBCreateTable($prefix . '_block_group_instances',
                                  array('xar_id'          => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'increment'   => true,
                                                                   'primary_key' => true),
                                        'xar_group_id'    => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0'),
                                        'xar_instance_id' => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0'),
                                        'xar_template'    => array('type'        => 'varchar',
                                                                   'size'        => 100,
                                                                   'null'        => true,
                                                                   'default'     => NULL),
                                        'xar_position'    => array('type'        => 'integer',
                                                                   'null'        => false,
                                                                   'default'     => '0')));
        
        $dbconn->Execute($query);
        
        $query = xarDBCreateIndex($prefix . '_block_group_instances',
                                  array('name' => 'i_' . $prefix . '_block_group_instances',
                                        'fields' => array('xar_group_id'),
                                        'unique' => false));
        $dbconn->Execute($query);
        
        $query = xarDBCreateIndex($prefix . '_block_group_instances',
                                  array('name' => 'i_' . $prefix . '_block_group_instances_2',
                                        'fields' => array('xar_instance_id'),
                                        'unique' => false));
        $dbconn->Execute($query);
        
        // Cache blocks table is not in xartables
        $cacheblockstable =  xarDBGetSystemTablePrefix() . '_cache_blocks';
        
        $query = xarDBCreateTable($prefix . '_cache_blocks',
                                  array('xar_bid'          => array('type'        => 'integer',
                                                                    'null'        => false,
                                                                    'default'     => '0',
                                                                    'primary_key' => true),
                                        'xar_nocache'    => array('type'        => 'integer',
                                                                  'null'        => false,
                                                                  'default'     => '0'),
                                        'xar_page' => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                        'xar_user'    => array('type'        => 'integer',
                                                               'null'        => false,
                                                               'default'     => '0'),
                                        'xar_expire'    => array('type'        => 'integer',
                                                                 'null'        => true)));
        $dbconn->Execute($query);
        
        // *_userblocks
        /* Removed Collapsing blocks to see if there is a better solution.
         $query = xarDBCreateTable($prefix . '_userblocks',
         array('xar_uid'         => array('type'    => 'integer',
         'null'    => false,
         'default' => '0'),
         'xar_bid'         => array('type'    => 'varchar',
         'size'    => 32,
         'null'    => false,
         'default' => '0'),
         'xar_active'      => array('type'    => 'integer',
         'size'    => 'tiny',
         'null'    => false,
         'default' => '1'),
         'xar_last_update' => array('type'    => 'timestamp',
         'null'    => false)));
         
         $result = $dbconn->Execute($query);
         
         $query = xarDBCreateIndex($prefix . '_userblocks',
         array('name'   => 'i_' . $prefix . '_userblocks',
         'fields' => array('xar_uid', 'xar_bid'),
         'unique' => true));
         $result = $dbconn->Execute($query);

         
         
         // Register BL tags
         sys::import('blocklayout.template.tags');
         xarTplRegisterTag('blocks', 'blocks-stateicon',
         array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
         'blocks_userapi_handleStateIconTag');
        */
        /* these can't be set because they are part of the core
         and when the core is installed, blocks is installed
         before the modules module is so, the module_vars table
         isn't even created at this point.
         
         xarModSetVar('blocks','collapseable',1);
         xarModSetVar('blocks','blocksuparrow','upb.gif');
         xarModSetVar('blocks','blocksdownarrow','downb.gif');
        */
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
        
    // Initialisation successful
    xarModSetVar('blocks', 'selstyle', 'plain');
    xarModSetVar('blocks', 'itemsperpage', 20);
        
    return true;
}

/**
 * upgrade the blocks module from an old version
 */
function blocks_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '1.0':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * delete the blocks module
 */
function blocks_delete()
{
  //this module cannot be removed
  return false;
}

?>
