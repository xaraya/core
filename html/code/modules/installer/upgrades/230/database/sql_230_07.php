<?php

/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_230_07()
{
    $dbconn = xarDB::getConn();
    $prefix = xarDB::getPrefix();
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

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
        $result = $stmt->executeQuery([]);
        $types = [];
        while ($result->next()) {
            [$id, $name, $module_id, $info, $module] = $result->fields;
            if (empty($module)) {
                $type_path = "blocks.$name.$name";
                $type_class = ucfirst($name) . 'Block';
            } else {
                $type_path = "modules.{$module}.xarblocks.{$name}";
                $type_class = ucfirst($module) . '_' . ucfirst($name) . 'Block';
            }
            sys::import($type_path);
            $object = new $type_class();
            $defaults = normalize_content($object->storeContent(), unserialize($info));
            $info = serialize($defaults);
            $category = $name != 'blockgroup' ? 'block' : 'group';
            $types[$id] = [
                'id' => $id, 'name' => $name, 'module_id' => $module_id, 'info' => $info, 'module' => $module,
                'state' => ixarBlock::TYPE_STATE_ACTIVE,'category' => $category, 'object' => $object,
            ];
        }
        $result->close();

        // get block instances from db
        $query = "SELECT instance.id, instance.type_id, instance.name, instance.title, instance.content,
                  instance.template, instance.state, instance.refresh, instance.last_update
                  FROM $instances_table AS instance";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([]);
        $instances = [];
        while ($result->next()) {
            [$id, $type_id, $name, $title, $content, $template, $state, $refresh, $last_update] = $result->fields;
            $type = $types[$type_id];
            $content = normalize_content($type['object']->storeContent(), unserialize($content));
            if (strpos($template, ';') !== false) {
                [$content['box_template'], $content['block_template']] = explode(';', $template);
            } else {
                $content['box_template'] = $template;
                $content['block_template'] = null;
            }
            if ($type['category'] == 'group') {
                $content['group_instances'] = [];
                // get instances belonging to this group
                $subquery = "SELECT groups.instance_id
                          FROM $groups_table AS groups WHERE groups.group_id = ?
                          ORDER BY groups.position";
                $bindvars = [$id];
                $substmt = $dbconn->prepareStatement($subquery);
                $subresult = $substmt->executeQuery($bindvars);
                while ($subresult->next()) {
                    [$instance_id] = $subresult->fields;
                    $content['group_instances'][] = $instance_id;
                }
                $subresult->close();
            } else {
                $content['instance_groups'] = [];
                // get groups this instance belongs to
                $subquery = "SELECT groups.group_id, groups.template
                             FROM $groups_table AS groups
                             WHERE groups.instance_id = ?";
                $bindvars = [$id];
                $substmt = $dbconn->prepareStatement($subquery);
                $subresult = $substmt->executeQuery($bindvars);
                while ($subresult->next()) {
                    [$group_id, $group_template] = $subresult->fields;
                    if (strpos($group_template, ';') !== false) {
                        [$box_template, $block_template] = explode(';', $group_template);
                    } else {
                        $box_template = $group_template;
                        $block_template = null;
                    }
                    $content['instance_groups'][$group_id] = [
                        'box_template' => $box_template,
                        'block_template' => $block_template,
                    ];
                }
                $subresult->close();
            }
            $instances[$id] = [
                'id' => $id,'type_id' => $type_id,'name' => $name,'title' => $title,
                'content' => serialize($content),'state' => $state,
            ];
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
        $fields = [
            'id' => [
                'type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'increment' => true,
                'primary_key' => true,
            ],
            'module_id' => [
                'type' => 'integer',
                'unsigned' => true,
                'null' => true,
            ],
            'state' => [
                'type' => 'integer',
                'size' => 'tiny',
                'unsigned' => true,
                'null' => false,
                'default' => ixarBlock::TYPE_STATE_ACTIVE,
            ],
            'type' => [
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ],
            'category' => [
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ],
            'info' => [
                'type' => 'text',
                'null' => true,
                'charset' => $charset,
            ],
        ];
        $query = xarTableDDL::createTable($types_table, $fields);
        $dbconn->Execute($query);

        // index columns
        $index = [
            'name' => 'i_' . $types_table . '_types',
            'fields' => ['type', 'module_id', 'state'],
            'unique' => true,
        ];
        $query = xarTableDDL::createIndex($types_table, $index);
        $dbconn->Execute($query);

        $index = [
            'name' => 'i_' . $types_table . '_category',
            'fields' => ['category'],
            'unique' => false,
        ];
        $query = xarTableDDL::createIndex($types_table, $index);
        $dbconn->Execute($query);

        // create block instances table
        $fields = [
            'id' => [
                'type' => 'integer',
                'unsigned' => true,
                'null' => false,
                'increment' => true,
                'primary_key' => true,
            ],
            'type_id' => [
                'type' => 'integer',
                'unsigned' => true,
                'null' => false,
            ],
            'state' => [
                'type' => 'integer',
                'size' => 'tiny',
                'unsigned' => true,
                'null' => false,
                'default' => ixarBlock::BLOCK_STATE_VISIBLE,
            ],
            'name' => [
                'type' => 'varchar',
                'size' => 64,
                'null' => false,
                'default' => null,
                'charset' => $charset,
            ],
            'title' => [
                'type' => 'varchar',
                'size' => 254,
                'null' => true,
                'default' => null,
                'charset' => $charset,
            ],
            'content' => [
                'type' => 'text',
                'null' => true,
                'charset' => $charset,
            ],
        ];
        $query = xarTableDDL::createTable($instances_table, $fields);
        $dbconn->Execute($query);

        // index columns
        $index = [
            'name' => 'i_' . $instances_table . '_instances',
            'fields' => ['name', 'state'],
            'unique' => true,
        ];
        $query = xarTableDDL::createIndex($instances_table, $index);
        $dbconn->Execute($query);

        $index = [
            'name' => 'i_' . $instances_table . '_type_id',
            'fields' => ['type_id'],
            'unique' => false,
        ];
        $query = xarTableDDL::createIndex($instances_table, $index);
        $dbconn->Execute($query);

        // insert types
        foreach ($types as $k => $type) {
            $id = $dbconn->genId($types_table);
            $query = "INSERT INTO $types_table
                (id, module_id, state, type, category, info)
                VALUES (?,?,?,?,?,?)";
            $bindvars = [
                $id, $type['module_id'], $type['state'], $type['name'], $type['category'], $type['info'],
            ];
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);
        }

        // insert instances
        foreach ($instances as $instance) {
            $id = $dbconn->genId($instances_table);
            $query = "INSERT INTO $instances_table
                (id, type_id, name, title, state, content)
                VALUES (?,?,?,?,?,?)";
            $bindvars = [
                $id, $instance['type_id'], $instance['name'], $instance['title'],
                $instance['state'], $instance['content'],
            ];
            $stmt = $dbconn->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);
        }

        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;

}

/**
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/200.html
 */
function normalize_content($defaults, $content)
{
    foreach ($defaults as $k => $v) {
        if (!isset($content[$k])) {
            $content[$k] = $v;
        }
    }
    return $content;
}
