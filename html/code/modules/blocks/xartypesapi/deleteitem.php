<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_typesapi_deleteitem(Array $args=array())
{
    if (empty($args['type_id']) || !is_numeric($args['type_id'])) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('type_id', 'blocks', 'typesapi', 'deleteitem');
        throw new EmptyParameterException($vars, $msg);
    }

    $type = xarMod::apiFunc('blocks', 'types', 'getitem',
        array('type_id' => $args['type_id']));
    
    if (!$type) {
        $msg = 'Block type id "#(1)" does not exist';
        $vars = array($type_id);
        throw new IdNotFoundException($vars, $msg);
    }

    $type_instances = xarMod::apiFunc('blocks', 'instances', 'getitems',
        array('type' => $type['type'], 'module' => $type['module']));
    
    if (!empty($type_instances)) {
        foreach (array_keys($type_instances) as $block_id) 
            if (!xarMod::apiFunc('types', 'instances', 'deleteitem',
                array('block_id' => $block_id))) return;
    }
    unset($type, $type_instances);
    
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $types_table = $tables['block_types'];

    $query = "DELETE FROM $types_table
              WHERE id = ?";
    $bindvars[] = $args['type_id'];
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    // @todo: block scope hooks
    /*
    $item = array(
        'module' => 'blocks', 
        'itemid' => $args['type_id'],
        'itemtype' => 1,
    );
    xarHooks::notify('BlockDelete', $item);
    */
    
    return $args['type_id'];
}
?>