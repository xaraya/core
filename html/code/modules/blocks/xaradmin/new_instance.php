<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Display type options, form and add a new block instance to the system
 * 
 * @author Jim McDonald
 * @author Paul Rosania
 * @author Chris Powis
 * 
 * @param void N/A
 * @return array Data display array
*/
function blocks_admin_new_instance()
{
    // @checkme: Add here vs Manage elsewhere ?
    // @checkme: Instance mask still relevent with anon masks in play?
    if (!xarSecurityCheck('AddBlocks', 1, 'Instance')) return;
    
    $data = array();
    
    if (!xarVarFetch('type_id', 'int:1:',
        $data['type_id'], null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:str:1:',
        $phase, 'options', XARVAR_NOT_REQUIRED)) return;

    $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
    
    // always validate type in form and update phase     
    if ($phase == 'form' || $phase == 'update') {
        if (empty($data['type_id'])) {
            // gotta have a type_id
            $invalid['type_id'] = xarML('You must select a block type for this instance');
        } else {
            // get the type
            $type = xarMod::apiFunc('blocks', 'types', 'getitem', 
                array('type_id' => $data['type_id']));
            if (!$type) {
                // type may have been removed since last phase
                $invalid['type_id'] = xarML('Block type id "#(1)" does not exist', $data['type_id']);
            } else {
                if ($type['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
                    // type state may have changed since last phase 
                    $invalid['type_id'] = xarML('Selected block type for this instance is not active');
                } elseif (!empty($type['type_info']['add_access'])) {
                    // Decide whether the current user can create blocks of this type
                    $args = array(
                        'component' => 'Block',
                        'instance' => $type['type_id'] . ":All:All",
                        'group' => $type['type_info']['add_access']['group'],
                        'level' => $type['type_info']['add_access']['level'],
                    );                
                    if (!$accessproperty->check($args)) {
                        // access may have changed since last phase 
                        $invalid['type_id'] = xarML('You do not have permission to create blocks of this type'); 
                    }
                }
            }
        }
        if (empty($invalid)) {
            // set defaults (form phase, 1st run) / fetch input (update phase) 
            if (!xarVarFetch('name', 'pre:trim:lower:str:1:64',
                $data['name'], '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('title', 'pre:trim:str:0:254',
                $data['title'], '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('state', 'int:0:3',
                $data['state'], null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('block_template', 'pre:trim:str:0:127',
                $data['block_template'], null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('box_template', 'pre:trim:str:0:127',
                $data['box_template'], null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('groups', 'array',
                $data['groups'], array(), XARVAR_NOT_REQUIRED)) return;
            // get the block type object
            $block_type = xarMod::apiFunc('blocks', 'blocks', 'getobject', $type);
            $instance_states = xarMod::apiFunc('blocks', 'instances', 'getstates');
            // get the list of registered block group types 
            $block_groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
                array(
                    'type_category' => 'group',
                    'type_state' => xarBlock::TYPE_STATE_ACTIVE,
                ));
        } else {
            // redisplay type options with invalid message 
            $phase = 'options';
            $data['invalid'] = $invalid;
        }
    }
    
    // deal with update phase - at this point we have a valid type which user can create instances of
    if ($phase == 'update') {
        // validations
        if (!xarSecConfirmAuthKey())
            return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        // groups, optional, if supplied must be valid block groups
        // validated here because createitem has no knowledge of them
        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $group_id) {
                if (!isset($block_groups[$group_id])) {
                    $invalid['groups'] = xarML('Unknown block group selected');
                    break;
                }
            }
        }
        if (empty($invalid)) {
            // the createitem function handles validation for everything else,
            // we just need to catch any exceptions
            try {
                // add groups
                if (!empty($data['groups'])) {
                    foreach ($data['groups'] as $group_id) 
                        $block_type->attachGroup($group_id);
                }
                // set templates
                $block_type->setBoxTemplate($data['box_template']);
                $block_type->setBlockTemplate($data['block_template']);
                // create item 
                $block_id = xarMod::apiFunc('blocks', 'instances', 'createitem',
                    array(
                        'type_id' => $data['type_id'],
                        'name' => $data['name'],
                        'title' => $data['title'],
                        'state' => $data['state'],
                        'content' => $block_type->storeContent(),
                    ));
                // add instance to selected groups 
                if (!empty($data['groups'])) {
                    foreach ($data['groups'] as $group_id) {
                        if (!isset($block_groups[$group_id])) continue;
                        $group = $block_groups[$group_id];
                        $block_group = xarMod::apiFunc('blocks', 'blocks', 'getobject', $group);
                        if (!$block_group) continue;
                        $result = $block_group->attachInstance($block_id);
                        if (!$result) continue;
                        $update = array(
                            'block_id' => $group_id,
                            'content' => $block_group->storeContent(),
                        );
                        if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $update)) continue;
                    }
                }
            } catch (BadParameterException $e) {
                // something wrong with args, see what it was                    
                // block name, required, can't be empty
                if (empty($data['name']) || strlen($data['name']) > 64) {
                    $invalid['name'] = xarML('Name must be a string between 1 and 64 characters long');
                } elseif (!preg_match('!^([a-z0-9_])*$!', $data['name'])) {
                    $invalid['name'] = xarML('Name can only contain the characters [a-z0-9_]');
                }   
                // state, required, must be a known state
                if (!isset($data['state']) || !isset($instance_states[$data['state']])) {
                    $invalid['state'] = xarML('Unknown block state selected');
                }
            } catch (DuplicateException $e) {
                // block instance with supplied name already exists
                $invalid['name'] = xarML('A block instance named "#(1)" already exists, name must be unique', $data['name']);
            } catch (IdNotFoundException $e) {
                // block type id doesn't exist
                // for this to happen here, the block type must have been deleted right after
                // we checked earlier and just before we called createitem, unlikely
                // however, since we can handle it if it does happen, let's do that
                $invalid['type_id'] = xarML('Block type id "#(1)" does not exist', $data['type_id']);
            } catch (Exception $e) {
                // if we're here, likely a db error, not much else we can do
                throw $e;
            }
        }
                    
        if (empty($invalid)) {
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'modify_instance', 
                    array('block_id' => $block_id));
            xarController::redirect($return_url);
            
        } else {
            // redisplay with invalid messages
            if (!empty($invalid['type_id'])) {
                // if we caught an id not found exception, return to options
                $phase = 'options';
            } else {
                // otherwise, show form
                $phase = 'form';
            }
            $data['invalid'] = $invalid;
        }
                    
    }         
    
    // deal with form phase - at this point we have a valid type which user can create instances of
    if ($phase == 'form') {
        // pass the type to template 
        $data['type'] = $type;
        // populate block state options
        $data['instance_states'] = $instance_states;
        if (!isset($data['state']))
            $data['state'] = xarBlock::BLOCK_STATE_VISIBLE;
        // populate with defaults from type on first run
        if (!isset($data['box_template']))
            $data['box_template'] = $type['type_info']['box_template'];
        if (!isset($data['block_template']))
            $data['block_template'] = $type['type_info']['block_template'];
        
        $group_options = array();
        foreach ($block_groups as $block_group) {
            $group_options[] = array(
                'id' => $block_group['block_id'],
                'name' => $block_group['name'],
           );
        }
        $data['group_options'] = $group_options;
    }    

    // option phase - at this point we're either on first run, or have an invalid type_id
    if ($phase == 'options') {
        // refresh block types
        if (!xarMod::apiFunc('blocks', 'types', 'refresh')) return;
        // get the list of active block types   
        $types = xarMod::apiFunc('blocks', 'types', 'getitems', 
            array('type_state' => xarBlock::TYPE_STATE_ACTIVE));
        // format types for dropdown 
        $type_options = array();
        foreach ($types as $k => $type) {
            if (!empty($type['type_info']['add_access'])) {
                // Decide whether the current user can create blocks of this type
                $args = array(
                    'component' => 'Block',
                    'instance' => $type['type_id'] . ":All:All",
                    'group' => $type['type_info']['add_access']['group'],
                    'level' => $type['type_info']['add_access']['level'],
                );                
                if (!$accessproperty->check($args)) {
                    unset($types[$k]);
                    continue;
                }
            }
            $type_options[] = array(
                'id' => $type['type_id'], 
                'name' => empty($type['module']) ? $type['type'] : $type['type'] .' ('.$type['module'] . ')',
            );
        }
        $data['type_options'] = $type_options;
    }

    $data['phase'] = $phase;
    
    return $data;                
}
?>