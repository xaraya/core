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

    // Order by columns.
    // Only id, type and module allowed.
    // Ignore order-clause silently if incorrect unmerated columns passed in.
    // TODO: this will now fail
    //if (!empty($order) && xarVarValidate('strlist:,|:pre:trim:passthru:enum:module:type:id', $order, true)) {
        //$orderby = ' ORDER BY xar_' . implode(', xar_', explode(',', $order));
    //} else {
        $orderby = '';
    //}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_types_table = $xartable['block_types'];
    $modules_table     = $xartable['modules'];

    // Fetch instance details.
    $query = "SELECT btypes.xar_id, mods.xar_name, btypes.xar_type, btypes.xar_info
              FROM  $block_types_table btypes, $modules_table mods
              WHERE btypes.xar_modid = mods.xar_id ";

    $bind = array();
    if (!empty($module)) {
        $query .= ' AND mods.xar_name = ?';
        $bind [] = $module;
    }

    if (!empty($type)) {
        $query .= ' AND btypes.xar_type = ?';
        $bind [] = $type;
    }

    if (!empty($tid) && is_numeric($tid)) {
        $query .= ' AND btypes.xar_id = ?';
        $bind [] = $tid;
    }
    $query .= $orderby;

    // Return if no details retrieved.
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bind);

    // The main result array.
    $types = array();
    while ($result->next()) {
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
    }
    // Close the query.
    $result->close();

    return $types;
}

?>
