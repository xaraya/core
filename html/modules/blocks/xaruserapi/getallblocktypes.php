<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/*
 * Get one or more block types.
 * @param args['tid'] block type ID (optional)
 * @param args['module'] module name (optional)
 * @param args['type'] block type name (optional)
 * @returns array of block types, keyed on block type ID
 *
 * @author Jason Judge
*/

function blocks_userapi_getallblocktypes($args)
{
    extract($args);

    $where = array();
    $bind = array();

    if (!empty($module)) {
        $where[] = 'xar_module = ?';
        $bind [] = $module;
    }

    if (!empty($type)) {
        $where[] = 'xar_type = ?';
        $bind [] = $type;
    }

    if (!empty($tid) && is_numeric($tid)) {
        $where[] = 'xar_id = ?';
        $bind [] = $tid;
    }

    // Order by columns.
    // Only id, type and module allowed.
    // Ignore order-clause silently if incorrect unmerated columns passed in.
    if (!empty($order) && xarVarValidate('strlist:,|:pre:trim:passthru:enum:module:type:id', $order, true)) {
        $orderby = ' ORDER BY xar_' . implode(', xar_', explode(',', $order));
    } else {
        $orderby = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_types_table = $xartable['block_types'];

    // Fetch instance details.
    $query = 'SELECT xar_id, xar_module, xar_type, xar_info'
        . ' FROM ' . $block_types_table;

    if (!empty($where)) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }

    $query .= $orderby;

    // Return if no details retrieved.
    $result =& $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    // The main result array.
    $types = array();

    while (!$result->EOF) {
        // Fetch instance data
        list($tid, $module, $type, $info) = $result->fields;

        if (!empty($info)) {
            // The info column contains structured data.
            // Unserialize it here to abstract the storage method.
            $info = @unserialize($info);
        }

        $types[$tid] = array(
            'tid' => $tid,
            'module' => $module,
            'type' => $type,
            'info' => $info
        );

        // Next block type.
        $result->MoveNext();
    }

    // Close the query.
    $result->Close();

    return $types;
}

?>
