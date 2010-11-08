<?php
/**
 * Count the number of block types
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Count the number of block types [of a given name or module]
 *
 * @author Jason Judge
 * @access public
 * @param modName the module name
 * @param $args['type'] name of the block type (optional)
 * @param $args['module'] name of the module (optional)
 * @return integer count of block types that meet the required criteria
 * @throws DATABASE_ERROR
 */
function blocks_userapi_countblocktypes($args)
{
    extract($args);

    $bind = array();

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_types_table = $xartable['block_types'];
    $modules_table     = $xartable['modules'];

    $query = "SELECT count(btypes.id)
              FROM $block_types_table btypes, $modules_table mods
              WHERE btypes.module_id = mods.id ";
    if(!empty($module)) {
        $query .= "AND mods.name = ? ";
        $bind[] = $module;
    }
    if (!empty($type)) {
        $query .= 'AND btypes.name = ?';
        $bind[] = $type;
    }
    $result =& $dbconn->Execute($query, $bind);

    list ($count) = $result->fields;
    return (int)$count;
}

?>