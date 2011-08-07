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
        $vars = array('block_id', 'blocks', 'instancesapi', 'deleteitem');
        throw new EmptyParameterException($vars, $msg);
    }

    $instance = xarMod::apiFunc('blocks', 'instances', 'getitem',
        array('block_id' => $args['block_id']));
    
    if (!$instance) {
        $msg = 'Block instance id "#(1)" does not exist';
        $vars = array($args['block_id']);
        throw new IdNotFoundException($vars, $msg);
    }
    
    try {
        $instance['method'] = 'delete';
        $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $instance);
    
        if ($instance['type_category'] == 'group') {
            $instance_ids = $block->getInstances();   
        } else {
            $group_ids = $block->getGroups();
        }
    
        // call block delete method if it has one
        $result = xarBlock::hasMethod($block, 'delete', true) ? $block->delete() : true;
        if (!$result) return;   
    } catch (Exception $e) {
        // this is ok, since we might need to remove instances when the type files are already gone
        if ($instance['type_category'] == 'group') {
            $instance_ids = $instance['content']['group_instances'];
        } else {
            $group_ids = $instance['content']['instance_groups'];
        }
    }
    
    if (!empty($group_ids) && is_array($group_ids)) {
        $instance_groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
            array('block_id' => array_keys($group_ids)));
        foreach (array_keys($group_ids) as $block_id) {
            if (!isset($instance_groups[$block_id])) continue;
            $g_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $instance_groups[$block_id]);
            $g_block->detachInstance($args['block_id']);
            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
                array(
                    'block_id' => $block_id,
                    'content' => $g_block->storeContent(),
                ))) return;
            unset($g_block);
        }
        unset($group_ids, $instance_groups); 
    } elseif (!empty($instance_ids) && is_array($instance_ids)) {
        $group_instances = xarMod::apiFunc('blocks', 'instances', 'getitems', 
            array('block_id' => $instance_ids));
        foreach ($instance_ids as $block_id) {
            if (!isset($group_instances[$block_id])) continue;
            $i_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $group_instances[$block_id]);
            $i_block->detachGroup($args['block_id']);
            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem',
                array(
                    'block_id' => $block_id,
                    'content' => $i_block->storeContent(),
                ))) return;
            unset($i_block);
        }
        unset($instance_ids, $group_instances);   
    }
    unset($instance, $block);

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $block_table = $tables['block_instances'];

    $query = "DELETE FROM $block_table
              WHERE id = ?";
    $bindvars[] = $args['block_id'];
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    // @todo: block scope hooks
    /*
    $item = array(
        'module' => 'blocks', 
        'itemid' => $args['block_id'],
        'itemtype' => 3,
    );
    xarHooks::notify('BlockDelete', $item);
    */
    return true;
        
}
?>