<?php
/**
 * Initialise the blocks module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage blocks
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * initialise the blocks module
 * @author Jim McDonald, Paul Rosania
 */
function blocks_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $prefix = xarDB::getPrefix();

    // Create tables inside a transaction
    try {
        $dbconn->begin();
        
        // prototypes
        $id_type       = array('type'=>'integer', 'unsigned'=>true, 'null'=>false, 'increment'=>true, 'primary_key'=>true);
        $idref_type    = array('type'=>'integer', 'unsigned'=>true, 'null'=>false);
        $template_type = array('type'=>'varchar', 'size'=>254, 'null'=>true, 'default'=>null);
        
        // *_block_groups
        $query = xarDBCreateTable($prefix . '_block_groups',
                                  array('id'          => $id_type,
                                        'name'        => array('type'        => 'varchar',
                                                                   'size'        => 64,
                                                                   'null'        => false),
                                        'template'    => $template_type));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_groups',
                                  array('name'   => $prefix . '_block_groups_name',
                                        'fields' => array('name'),
                                        'unique' => 'true'));
        $dbconn->Execute($query);

        // *_block_instances
        $query = xarDBCreateTable($prefix . '_block_instances',
                                  array('id'          => $id_type,
                                        'type_id'     => $idref_type,
                                        'name'       => array('type'        => 'varchar',
                                                                  'size'        => 64,
                                                                  'null'        => false,
                                                                  'default'     => NULL),
                                        'title'       => array('type'        => 'varchar',
                                                                   'size'        => 254,
                                                                   'null'        => true,
                                                                   'default'     => NULL),
                                        'content'     => array('type'        => 'text',
                                                                   'null'        => false),
                                        'template'    => $template_type,
                                        'state'       => array('type'        => 'integer',
                                                                   'size'        => 'tiny',
                                                                   'unsigned'    => true,
                                                                   'null'        => false,
                                                                   'default'     => '2'),
                                        'refresh'     => array('type'        => 'boolean',
                                                                   'default'     => false),
                                        'last_update' => array('type'        => 'integer',
                                                                   'unsigned'    => true,
                                                                   'null'        => false,
                                                                   'default'     => '0')));

        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_instances',
                                  array('name'   => $prefix . '_block_instances_type_id',
                                        'fields' => array('type_id'),
                                        'unique' => false));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_instances',
                                  array('name'   => $prefix . '_block_instances_u2',
                                        'fields' => array('name'),
                                        'unique' => true));
        $dbconn->Execute($query);

        // *_block_types
        $query = xarDBCreateTable($prefix . '_block_types',
                                  array(
                                        'id' => $id_type,
                                        'name' => array(
                                                            'type'          => 'varchar',
                                                            'size'          => 64,
                                                            'null'          => false,
                                                            ),
                                        'module_id' => $idref_type,
                                        'info' => array(
                                                            'type'          => 'text',
                                                            'null'          => true
                                                            )
                                        )
                                  );

        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_types',
                                  array('name'   => $prefix . '_block_types2',
                                        'fields' => array('module_id', 'name'),
                                        'unique' => 'false'));
        $dbconn->Execute($query);
        /*
         TODO: Find a fix for this - Postgres will not allow partial indexes
         $query = xarDBCreateIndex($prefix . '_block_types',
         array('name'   => $prefix . '_block_types_2',
         'fields' => array('name(50)', 'module_id(50)'),
         'unique' => true));
         $result =& $dbconn->Execute($query);
        */
        // *_block_group_instances
        $query = xarDBCreateTable($prefix . '_block_group_instances',
                                  array('id'          => $id_type,
                                        'group_id'    => $idref_type,
                                        'instance_id' => $idref_type,
                                        'template'    => $template_type,
                                        'position'    => array('type'            => 'integer',
                                                                   'size'        => 'tiny',
                                                                   'unsigned'    => true,
                                                                   'null'        => false)));

        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_group_instances',
                                  array('name' => $prefix . '_block_group_instances_group_id',
                                        'fields' => array('group_id'),
                                        'unique' => false));
        $dbconn->Execute($query);

        $query = xarDBCreateIndex($prefix . '_block_group_instances',
                                  array('name' => $prefix . '_block_group_instances_instance_id',
                                        'fields' => array('instance_id'),
                                        'unique' => false));
        $dbconn->Execute($query);

        // Cache blocks table is not in xartables
        $cacheblockstable =  $prefix . '_cache_blocks';

        $query = xarDBCreateTable($prefix . '_cache_blocks',
                                  array('blockinstance_id'          => array('type'        => 'integer',
                                                                    'unsigned'    => true,
                                                                    'null'        => false,
                                                                    'primary_key' => true),
                                        'nocache'    => array('type'        => 'integer',
                                                                    'size'        => 'tiny',
                                                                    'null'        => false,
                                                                  'default'     => '0'),
                                        'page' => array('type'        => 'integer',
                                                            'null'        => false,
                                                            'default'     => '0'),
                                        'theuser'    => array('type'        => 'integer',
                                                               'unsigned'    => true,
                                                               'null'        => false),
                                        'expire'    => array('type'        => 'integer',
                                                               'unsigned'    => true,
                                                               'default'     => '0')));
        $dbconn->Execute($query);

        // *_userblocks
        /* Removed Collapsing blocks to see if there is a better solution.
         $query = xarDBCreateTable($prefix . '_userblocks',
         array('id'         => array('type'    => 'integer',
         'null'    => false,
         'default' => '0'),
         'bid'         => array('type'    => 'varchar',
         'size'    => 32,
         'null'    => false,
         'default' => '0'),
         'active'      => array('type'    => 'integer',
         'size'    => 'tiny',
         'null'    => false,
         'default' => '1'),
         'last_update' => array('type'    => 'timestamp',
         'null'    => false)));

         $result = $dbconn->Execute($query);

         $query = xarDBCreateIndex($prefix . '_userblocks',
         array('name'   => $prefix . '_userblocks',
         'fields' => array('id', 'bid'),
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

         xarModVars::set('blocks','collapseable',1);
         xarModVars::set('blocks','blocksuparrow','upb.gif');
         xarModVars::set('blocks','blocksdownarrow','downb.gif');
        */
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Initialisation successful
    xarModVars::set('blocks', 'selstyle', 'plain');
    xarModVars::set('blocks', 'itemsperpage', 20);

    return blocks_upgrade('1.0');
}

/**
 * upgrade the blocks module from an old version
 */
function blocks_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0':
        case '2.1':
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
