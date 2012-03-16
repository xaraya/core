<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_230_07()
{
    $dbconn = xarDB::getConn();
    $prefix = xarDB::getPrefix();
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    sys::import('xaraya.tableddl');
    
    $types_table = "{$prefix}_block_types";
    $instances_table = "{$prefix}_block_instances";
    $groups_table = "{$prefix}_block_group_instances";
    $cache_table = "{$prefix}_cache_blocks";
    $modules_table = "{$prefix}_modules";
    
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Refactoring block tables
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the query
    try {
        $dbconn->begin();
        
        // get block types from db        
        $query = "SELECT type.id, type.name, type.module_id, type.info, mods.name
                  FROM $types_table AS type
                  LEFT JOIN $modules_table as mods ON type.module_id = mods.id";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array());
        $types = array();
        while($result->next()) {      
            list($id, $name, $module_id, $info, $module) = $result->fields;
            if (empty($module)) {
                $type_path = "blocks.$name.$name";
                $type_class = ucfirst($name).'Block';
            } else {
                $type_path = "modules.{$module}.xarblocks.{$name}";
                $type_class = ucfirst($module).'_'.ucfirst($name).'Block';
            }
            sys::import($type_path);
            $object = new $type_class();
            $defaults = normalize_content($object->storeContent(), unserialize($info));
            $info = serialize($defaults);
            $category = $name != 'blockgroup' ? 'block' : 'group';
            $types[$id] = array(
                'id' => $id, 'name' => $name, 'module_id' => $module_id, 'info' => $info, 'module' => $module,
                'state' => xarBlock::TYPE_STATE_ACTIVE,'category' => $category, 'object' => $object,
            );
        }
        $result->close();
        
        // get block instances from db
        $query = "SELECT instance.id, instance.type_id, instance.name, instance.title, instance.content,
                  instance.template, instance.state, instance.refresh, instance.last_update
                  FROM $instances_table AS instance";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery(array());
        $instances = array();
        while($result->next()) {      
            list($id,$type_id,$name,$title,$content,$template,$state,$refresh,$last_update) = $result->fields;
            $type = $types[$type_id];
            $content = normalize_content($type['object']->storeContent(), unserialize($content));
            if (strpos($template, ';') !== false) {
                list($content['box_template'], $content['block_template']) = explode(';', $template);
            } else {
                $content['box_template'] = $template;
                $content['block_template'] = null;
            }               
            if ($type['category'] == 'group') {
                $content['group_instances'] = array();
                // get instances belonging to this group
                $subquery = "SELECT groups.instance_id
                          FROM $groups_table AS groups WHERE groups.group_id = ?
                          ORDER BY groups.position";
                $bindvars = array($id);
                $substmt = $dbconn->prepareStatement($subquery);
                $subresult = $substmt->executeQuery($bindvars);
                while($subresult->next()) {
                    list($instance_id) = $subresult->fields;
                    $content['group_instances'][] = $instance_id;
                }
                $subresult->close();
            } else {
                $content['instance_groups'] = array();
                // get groups this instance belongs to
                $subquery = "SELECT groups.group_id, groups.template
                             FROM $groups_table AS groups
                             WHERE groups.instance_id = ?";
                $bindvars = array($id);
                $substmt = $dbconn->prepareStatement($subquery);
                $subresult = $substmt->executeQuery($bindvars);
                while($subresult->next()) {
                    list($group_id, $group_template) = $subresult->fields;
                    if (strpos($group_template, ';') !== false) {
                        list($box_template, $block_template) = explode(';', $group_template);
                    } else {
                        $box_template = $group_template;
                        $block_template = null;
                    }                    
                    $content['instance_groups'][$group_id] = array(
                        'box_template' => $box_template,
                        'block_template' => $block_template,
                    );
                }
                $subresult->close();
            }
            $instances[$id] = array(
                'id' => $id,'type_id' => $type_id,'name' => $name,'title' => $title,
                'content' => serialize($content),'state' => $state,
            );
        }
        $result->close();
        
        // drop all blocks tables      
        $query = "DROP TABLE IF EXISTS $types_table";
        $dbconn->Execute($query);
        $query = "DROP TABLE IF EXISTS $instances_table";
        $dbconn->Execute($query);
        $query = "DROP TABLE IF EXISTS $groups_table";
        $dbconn->Execute($query);
        $query = "DROP TABLE IF EXISTS $cache_table";
        $dbconn->Execute($query);
        
        // create block types table 
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
                'default' => xarBlock::TYPE_STATE_ACTIVE,
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

        // index columns
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
        
        // create block instances table
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
            'state' => array(
                'type' => 'integer',
                'size' => 'tiny',
                'unsigned' => true,
                'null' => false,
                'default' => xarBlock::BLOCK_STATE_VISIBLE,
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
        );
        $query = xarDBCreateTable($instances_table, $fields); 
        $dbconn->Execute($query);
        
        // index columns
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

        // insert types
        foreach ($types as $k => $type) {
            $id = $dbconn->genId($types_table);
            $query = "INSERT INTO $types_table
                (id, module_id, state, type, category, info)
                VALUES (?,?,?,?,?,?)";
            $bindvars = array(
                $id, $type['module_id'], $type['state'], $type['name'], $type['category'], $type['info']
            );
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);
        }

        // insert instances 
        foreach ($instances as $instance) {
            $id = $dbconn->genId($instances_table);
            $query = "INSERT INTO $instances_table
                (id, type_id, name, title, state, content)
                VALUES (?,?,?,?,?,?)";
            $bindvars = array(
                $id, $instance['type_id'], $instance['name'], $instance['title'], 
                $instance['state'], $instance['content'],
            );
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);
        }
        
        $dbconn->commit();     
    } catch (Exception $e) { throw($e);
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;   
    
}

function normalize_content($defaults, $content)
{
    foreach ($defaults as $k => $v) {
        if (!isset($content[$k])) {
            $content[$k] = $v;
        }
    }
    return $content;
}

?>