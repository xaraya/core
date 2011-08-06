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
function blocks_instancesapi_deleteitem(Array $args=array())
{
    if (empty($args['block_id']) || !is_numeric($args['block_id'])) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('block_id', 'blocks', 'instances', 'deleteitem');
        throw new EmptyParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $block_table = $tables['block_instances'];

    $query = "DELETE FROM $block_table
              WHERE id = ?";
    $bindvars[] = $args['block_id'];
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;
    return true;

    // @todo: block hooks 
    $item = array(
        'module' => 'blocks', 
        'itemid' => $args['block_id'],
        'itemtype' => 3,
    );
    xarHooks::notify('BlockDelete', $item);
    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'delete', $bid, $args);

    return true;
        
}
?>