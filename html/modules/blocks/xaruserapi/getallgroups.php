<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Get a single block type.
 *
 * @param gid Group ID (optional)
 * @param name Group name (optional)
 * @returns array of block groups, keyed on block group ID (gid)
 * @author Jason Judge
 */

function blocks_userapi_getallgroups($args)
{
    extract($args);

    $where = array();
    $bind = array();

    if (!empty($gid)) {
        $where[] = 'id = ?';
        $bind[] = $gid;
    }

    if (!empty($name)) {
        $where[] = 'name = ?';
        $bind[] = $name;
    }

    if (!empty($where)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where);
    } else {
        $where_clause = '';
    }

    // Can order by name and id
    if (!empty($order) && xarVarValidate('strlist:,|:pre:trim:passthru:enum:name:id', $order, true)) {
        $orderby = ' ORDER BY ' . $order;
    } else {
        $orderby = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_groups_table =& $xartable['block_groups'];
    $query = 'SELECT id as gid, name as name, template as template'
        . ' FROM ' . $block_groups_table . $where_clause . $orderby;
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bind,ResultSet::FETCHMODE_ASSOC);

    $block_groups = array();
    while($result->next()) {
        $group = $result->fields;
        $block_groups[$group['gid']] = $group;
    }

    return $block_groups;
}

?>
