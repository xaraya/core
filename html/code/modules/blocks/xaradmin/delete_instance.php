<?php
/**
 * Block management - delete a block
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * delete a block instance
 * @author Jim McDonald
 * @author Paul Rosania
 */
function blocks_admin_delete_instance()
{
    if (!xarSecurityCheck('ManageBlocks')) return;

    if (!xarVarFetch('block_id', 'int:1:',
        $block_id, null, XARVAR_NOT_REQUIRED)) return;

    if (!isset($block_id)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('block_id', 'blocks', 'admin', 'delete_instance');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $instance = xarMod::apiFunc('blocks', 'instances', 'getitem',
        array('block_id' => $block_id));
    
    if (!$instance) {
        $msg = 'Block instance id "#(1)" does not exist';
        $vars = array($block_id);
        throw new IdNotFoundException($vars, $msg);
    }

    // admin access is needed for some operations 
    $isadmin = xarSecurityCheck('',0,'Block',"$instance[type]:$instance[name]:$instance[block_id]",$instance['module'],'',0,800);
    $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
    // check delete access 
    if ($isadmin) {
        $candelete = true;
    } else {
        $args = array(
            'module' => $instance['module'],
            'component' => 'Block',
            'instance' => "$instance[type]:$instance[name]:$instance[block_id]",
            'group' => $instance['content']['delete_access']['group'],
            'level' => $instance['content']['delete_access']['level'],
        );
        $candelete = $accessproperty->check($args);
    }
    if (!$candelete)
        return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));

    if (!xarVarFetch('confirm', 'checkbox', 
        $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    $instance['method'] = 'delete';
    $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $instance);

    // if block group, get instances attached to it    
    if ($instance['type_category'] == 'group') {
        $group_instances = $block->getInstances();
        if (!empty($group_instances))
            $block_instances = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array('block_id' => $group_instances));
    } 
    // else, get groups instance is attached to    
    else {
        $instance_groups = $block->getGroups();
        if (!empty($instance_groups))
            $block_groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array('type_category' => 'group', 'block_id' => array_keys($instance_groups)));
    }
    
    if ($confirmed) {
        // call block delete method if it has one
        $result = xarBlock::hasMethod($block, 'delete', true) ? $block->delete() : true;
        if (!$result) return;
        
        // delete instance from db
        if (!xarMod::apiFunc('blocks', 'instances', 'deleteitem',
            array('block_id' => $instance['block_id']))) return;

        // detach instance from groups if it's in any 
        if (!empty($instance_groups)) {
            foreach (array_keys($instance_groups) as $group_id) {
                if (!isset($block_groups[$group_id])) continue;
                $group_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_groups[$group_id]);
                $group_block->detachInstance($instance['block_id']);
                $group_update = array(
                    'block_id' => $group_id,
                    'content' => $group_block->storeContent(),
                );
                if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $group_update)) return;
                unset($group_block, $group_update);
            }
        }
        // else detach group from instances if it has any
        elseif (!empty($group_instances)) {
            foreach ($group_instances as $instance_id) {
                if (!isset($block_instances[$instance_id])) continue;
                $instance_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_instances[$instance_id]);
                $instance_block->detachGroup($instance['block_id']);
                $instance_update = array(
                    'block_id' => $instance_id,
                    'content' => $instance_block->storeContent(),
                );
                if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $instance_update)) return;
                unset($instance_block, $instance_update);
            }
        }
        $return_url = xarModURL('blocks', 'admin', 'view_instances');
        xarController::redirect($return_url);
    }
    
    $data = array();
    
    $data['instance'] = $instance;
    if (!empty($block_instances))
        $data['block_instances'] = $block_instances;
    if (!empty($block_groups))
        $data['block_groups'] = $block_groups;
        
    return $data;
}
?>