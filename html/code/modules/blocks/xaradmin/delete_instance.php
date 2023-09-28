<?php
/**
 * Block management - delete a block
 * 
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 */

/**
 * 
 * Delete a block instance
 * 
 * @author Jim McDonald
 * @author Paul Rosania
 * 
 * @return array<mixed>|string|void Data array
 * @throws EmptyParameterException Thrown if no block id has been passed
 * @throws IDNotFoundException Thrown if no block with the given block id was found in the API
 */
function blocks_admin_delete_instance()
{
    if (!xarSecurity::check('ManageBlocks')) return;

    if (!xarVar::fetch('block_id', 'int:1:',
        $block_id, null, xarVar::NOT_REQUIRED)) return;

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
        throw new IDNotFoundException($vars, $msg);
    }

    // admin access is needed for some operations 
    $isadmin = xarSecurity::check('',0,'Block',"$instance[type]:$instance[name]:$instance[block_id]",$instance['module'],'',0,800);
    /** @var AccessProperty $accessproperty */
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

    if (!xarVar::fetch('confirm', 'checkbox', 
        $confirmed, false, xarVar::NOT_REQUIRED)) return;
    
    if ($confirmed) {
        if (!xarSec::confirmAuthKey())
            return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        
        // delete instance from db
        try {
            if (!xarMod::apiFunc('blocks', 'instances', 'deleteitem',
                array('block_id' => $instance['block_id']))) return;
        } catch (IDNotFoundException $e) {
            // ok, it's already gone
        } catch (Exception $e) {
            // oops, throw back
            throw $e;
        }
            
        $return_url = xarController::URL('blocks', 'admin', 'view_instances');
        xarController::redirect($return_url);
    }
    
    $data = array();
    $data['instance'] = $instance;
    try {
        $instance['method'] = 'delete';
        $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $instance);

        if ($instance['type_category'] == 'group') {
            $instance_ids = $block->getInstances();
        } else {
            $group_ids = $block->getGroups();
        }
    } catch (Exception $e) {
        // this is ok, since the files could be missing 
        if ($instance['type_category'] == 'group') {
            $instance_ids = $instance['content']['group_instances'];
        } else {
            $group_ids = $instance['content']['instance_groups'];
        }
    }
    if (!empty($instance_ids) && is_array($instance_ids)) {
        $data['group_instances'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
            array('block_id' => $instance_ids));
    } elseif (!empty($group_ids) && is_array($group_ids)) {
        $data['instance_groups'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
            array('type_category' => 'group', 'block_id' => array_keys($group_ids)));
    }
        
    return $data;
}
