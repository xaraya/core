<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Deletes an item from the API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return integer Returns given type id
 * @throws EmptyParameterException
 * @throws IdNotFoundException
 */
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
        foreach (array_keys($type_instances) as $block_id) {
            try {
                if (!xarMod::apiFunc('types', 'instances', 'deleteitem',
                    array('block_id' => $block_id))) return;
            } catch (IdNotFoundException $e) {
                // this is ok, it may already have been deleted
                continue;
            } catch (Exception $e) {
                // oops, throw back
                throw $e;
            }
        }
    }
    unset($type, $type_instances);
    
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
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