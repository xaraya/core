<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Get one or a number of block instances.
 *
 * @param args[$bid] optional block instance ID
 * @param args[$name] optional block instance name
 * @param args[$type] optional block instance type
 * @param args[$module] optional module block type belongs to
 * @param args[$state] optional state of block
 * @param args[$gid] optional group block(s) belong(s) to
 * @param args[$order] optional ordering
 * @param args[$startat] optional query offset (deprec)
 * @param args[$startnum] optional query offset
 * @param args[$rowstodo] optional limit items to return (deprec)
 * @param args[$numitems] optional limit items to return
 * @author Jim McDonald
 * @author Paul Rosania
 * @author Chris Powis
 * @param array    $args array of optional parameters<br/>
 * @throws DB_EXCEPTION
 * @return array of block instances
*/

function blocks_userapi_getall(Array $args=array())
{
    extract($args);

    // Check parameters
    if (!empty($bid) && !xarVarValidate('int:1:', $bid)) {return;}
    if (!empty($name) && !xarVarValidate('str', $name)) {return;}
    if (!empty($type) && !xarVarValidate('str', $type)) {return;}
    if (!empty($module) && !xarVarValidate('str', $module)) {return;}
    if (!empty($gid) && !xarVarValidate('int:1:', $gid)) {return;}

    // @TODO: Legacy params, remove once all functions no longer use them
    if (!isset($startnum) && isset($startat)) $startnum = $startat;
    if (!isset($numitems) && isset($rowstodo)) $numitems = $rowstodo;

    $startnum = !empty($startnum) && xarVarValidate('int:1:', $startnum, true) ? $startnum : 1;
    $numitems = !empty($numitems) && xarVarValidate('int:1:', $numitems, true) ? $numitems : 0;
    $order = !empty($order) && xarVarValidate('strlist:,|:enum:name:title:id:type:group', $order, true) ? $order : '';
    $filter = !empty($filter) && xarVarValidate('str:1:', $filter, true) ? $filter : '';

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_instances = $xartable['block_instances'];
    $group_instances = $xartable['block_group_instances'];
    $block_types     = $xartable['block_types'];
    $modules_table   = $xartable['modules'];

    $where = array();
    $bindvars = array();

    if ($order == 'group') {
        // get instances from block group instances table
        $query = "SELECT
            block.id,
            block.name,
            block.title,
            block.template,
            block.content,
            block.refresh,
            block.state,
            blocktype.id,
            module.name,
            blocktype.name,
            blockgroup.id,
            blockgroup.name,
            blockgroup.template,
            instance.position,
            instance.template
            FROM $modules_table module, $group_instances instance
            LEFT JOIN $block_instances block ON block.id = instance.instance_id
            INNER JOIN $block_instances blockgroup ON blockgroup.id = instance.group_id
            LEFT JOIN $block_types blocktype ON blocktype.id = block.type_id
            ";
    } else {
        // get instances from block instances table
        $query = "SELECT
            block.id,
            block.name,
            block.title,
            block.template,
            block.content,
            block.refresh,
            block.state,
            blocktype.id,
            module.name,
            blocktype.name
            FROM $modules_table module, $block_instances block
            LEFT JOIN $block_types blocktype  ON blocktype.id = block.type_id
            ";
    }
    $where[] = "module.id = blocktype.module_id";

    if (!empty($bid)) {
        $where[] = "block.id = ?";
        $bindvars[] = $bid;
    } elseif (!empty($name)) {
        $where[] = "block.name = ?";
        $bindvars[] = $name;
    } elseif (!empty($filter)) {
        $where[] = "lower(block.name) LIKE ?";
        $bindvars[] = '%'. strtolower($filter) . '%';
    }

    if (!empty($type)) {
        $where[] = "blocktype.name = ?";
        $bindvars[] = $type;
    }
    if (!empty($module)) {
        $where[] = "module.name = ?";
        $bindvars[] = $module;
    }
    if (isset($state)) {
        $where[] = "block.state = ?";
        $bindvars[] = $state;
    }
    if (!empty($gid) && $order == 'group') {
        $where[] = "instance.group_id = ?";
        $bindvars[] = $gid;
    }

    if (!empty($where)) $query .= " WHERE " . join (" AND ", $where);

    if (!empty($order)) {
        if (xarVarValidate('strlist:,|:enum:name:title:id', $order, true)) {
            $query .= ' ORDER BY block.' . $order;
        } elseif ($order == 'type') {
            $query .= ' ORDER BY blocktype.name';
        } elseif ($order == 'group') {
            //$query .= ' ORDER BY blockgroup.name, block.name';
            $query .= ' ORDER BY blockgroup.name, instance.position';
        }
    }

    // Prepare it
    $stmt = $dbconn->prepareStatement($query);

    // Return if no details retrieved
    if (!empty($startnum) && !empty($numitems)) {
        $stmt->setLimit($numitems);
        $stmt->setOffset($startnum - 1);
    }
    $result = $stmt->executeQuery($bindvars);

    $instances = array();
    if ($order =='group') {
        while ($result->next()) {
            // Fetch instance data
            list($bid, $name, $title, $template, $content, $refresh, $state, $tid, $module, $type, $gid, $group, $group_template, $position, $group_inst_template) = $result->fields;

            // The content no longer needs to be serialized for block functions.
            // Lets keep the un-serialization close to where it is stored (since
            // storage is the only reason we do it).
            if (!empty($content) && !is_array($content)) $content = @unserialize($content);
            if (!is_array($content)) $content = array();

            // TODO: is we use assoc fetching we get this for free
            $instance = array(
                'bid'       => $bid,
                'name'      => $name,
                'title'     => $title,
                'template'  => $template,
                'content'   => $content, //@TODO: unserialize this?
                'refresh'   => $refresh,
                'state'     => $state,
                'tid'       => $tid,
                'module'    => $module,
                'type'      => $type,
                'groupid'   => $gid,
                'group'     => $group,
                'group_template' => $group_template,
                'position'  => $position,
                'group_inst_template' => $group_inst_template,
                );

            // Put the instance into the result array.
            // Using references helps prevent copying data structures around.
            $instances[$bid] =& $instance;
            unset($instance);
        }
    } else {
        $querygroup = "SELECT
            instance.id,
            instance.group_id,
            instance.position,
            instance.template,
            blockgroup.name,
            blockgroup.template
            FROM $group_instances instance
            LEFT JOIN $block_instances blockgroup on blockgroup.id = instance.group_id
            WHERE instance.instance_id = ?
            ";
        $grpStmt = $dbconn->prepareStatement($querygroup);

        while ($result->next()) {
            // Fetch instance data
            list($bid, $name, $title, $template, $content, $refresh, $state, $tid, $module, $type) = $result->fields;

            // The content no longer needs to be serialized for block functions.
            // Lets keep the un-serialization close to where it is stored (since
            // storage is the only reason we do it).
            if (!empty($content) && !is_array($content)) $content = @unserialize($content);
            if (!is_array($content)) $content = array();

            // TODO: is we use assoc fetching we get this for free
            $instance = array(
                'bid'       => $bid,
                'name'      => $name,
                'title'     => $title,
                'template'  => $template,
                'content'   => $content,
                'refresh'   => $refresh,
                'state'     => $state,
                'tid'       => $tid,
                'module'    => $module,
                'type'      => $type,
                'groups'    => array(),
                );

            // Fetch group details - there may be none, one or many groups.
            $resultgroup = $grpStmt->executeQuery(array($bid));
            while ($resultgroup->next()) {
                list($giid, $id, $position, $group_inst_template, $name, $group_template) = $resultgroup->fields;

                // TODO: if we use assoc fetching we get this for free
                $group_instance = array(
                    'giid'      => $giid,
                    'id'       => $id,
                    'position'  => $position,
                    'name'      => $name,
                    // Return the original templates values as well as the over-riding templates.
                    'group_template'      => $group_template,
                    'group_inst_template' => $group_inst_template
                );
                $instance['groups'][$id] = $group_instance;
            }
            // Close group query.
            $resultgroup->close();

            // Put the instance into the result array.
            // Using references helps prevent copying data structures around.
            $instances[$bid] =& $instance;
            unset($instance);
        }
    }

    // Close main query.
    $result->close();
    return $instances;

}
?>