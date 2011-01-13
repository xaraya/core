<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Get one or more block types.
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['tid'] block type ID (optional)<br/>
 *        string   $args['module'] module name (optional)<br/>
 *        string   $args['type'] block type name (optional)
 * @return array the block types, keyed on block type ID
 *
 * @author Jason Judge
*/

function blocks_userapi_getallblocktypes(Array $args=array())
{
    extract($args);

    // Order by columns.
    // Only id, type and module allowed.
    // Ignore order-clause silently if incorrect numerated columns passed in.

    if (!empty($order) && xarVarValidate('strlist:,|:pre:trim:passthru:enum:module:type:id', $order, true)) {
        $orderby = ' ORDER BY ' . $order;
        $orderby = str_ireplace("module", "mods.name", $orderby);
        $orderby = str_ireplace("type", "btypes.name", $orderby);
    } else {
        $orderby = '';
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_types_table = $xartable['block_types'];
    $modules_table     = $xartable['modules'];

    // Fetch instance details.
    $query = "SELECT btypes.id, mods.name, btypes.name, btypes.info
              FROM  $block_types_table btypes, $modules_table mods
              WHERE btypes.module_id = mods.id ";

    $bind = array();
    if (!empty($module)) {
        $query .= ' AND mods.name = ?';
        $bind [] = $module;
    }

    if (!empty($type)) {
        $query .= ' AND btypes.name = ?';
        $bind [] = $type;
    }

    if (!empty($tid) && is_numeric($tid)) {
        $query .= ' AND btypes.id = ?';
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
