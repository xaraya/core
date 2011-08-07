<?php
/**
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
 * modify a block instance
 * @TODO Need to sperate this out to API calls.
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
 */

function blocks_admin_modify_instance()
{
    if (!xarSecurityCheck('ManageBlocks')) return;

    if (!xarVarFetch('block_id', 'int:1:',
        $block_id, null, XARVAR_NOT_REQUIRED)) return;

    if (!isset($block_id)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('block_id', 'blocks', 'admin', 'modify_instance');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $instance = xarMod::apiFunc('blocks', 'instances', 'getitem',
        array('block_id' => $block_id));
    
    if (!$instance) {
        $msg = 'Block instance id "#(1)" does not exist';
        $vars = array($block_id);
        throw new IdNotFoundException($vars, $msg);
    }

    $data = array();

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $data['tab'], 'info', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:str:1:',
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;

    // show the status warning if the type isn't active
    if ($instance['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
        $data['tab'] = 'status';
        $phase = 'form';
    }    

    // admin access is needed for some operations 
    $isadmin = xarSecurityCheck('',0,'Block',"$instance[type]:$instance[name]:$instance[block_id]",$instance['module'],'',0,800);
    $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
    // check modify access 
    if ($isadmin) {
        $canmodify = true;
    } else {
        $args = array(
            'module' => $instance['module'],
            'component' => 'Block',
            'instance' => "$instance[type]:$instance[name]:$instance[block_id]",
            'group' => $instance['content']['modify_access']['group'],
            'level' => $instance['content']['modify_access']['level'],
        );
        $canmodify = $accessproperty->check($args);
    }

    $instance_states = xarMod::apiFunc('blocks', 'instances', 'getstates');
    
    // only get the block instance if the type is active 
    if ($data['tab'] != 'status') {
        $filter = $instance;
        switch ($data['tab']) {
            case 'info':
            case 'preview':
                // managers can see these 
                $filter['method'] = 'display';
            break;
            case 'help':
                $filter['method'] = 'help';
            break;
            case 'config':
                if (!$canmodify)
                    return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
                $filter['method'] = 'modify';
            break;
            case 'access':
            case 'caching':
                // admin only here 
                if (!$isadmin)
                    return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
                $filter['method'] = 'modify';
            break;
            default:
                if (!$canmodify)
                    return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
                if (!xarVarFetch('method', 'pre:trim:str:1:',
                    $filter_method, '', XARVAR_NOT_REQUIRED)) return; 
                //if (empty($filter_method))
                    //$filter_method = $phase == 'update' ? 'update'.$data['tab'] : $data['tab'];
                $filter['method'] = $filter_method; 
            break;
        }

        // get the block instance
        $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $filter);

        $block_groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
            array('type_category' => 'group',));

        // if block group, get instances attached to it    
        if ($instance['type_category'] == 'group') {
            $group_instances = $block->getInstances();
            if (!empty($group_instances))
                $data['group_instances'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
                    array('block_id' => $group_instances));
        } 
        // else, get groups instance is attached to    
        else {
            $instance_groups = $block->getGroups();
            if (!empty($instance_groups))
                $data['instance_groups'] = xarMod::apiFunc('blocks', 'instances', 'getitems',
                    array('type_category' => 'group', 'block_id' => array_keys($instance_groups)));
        }

    }
       
    // update phase 
    if ($phase == 'update') {

        if (!xarSecConfirmAuthKey())
            return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        
        $invalid = array();      

        switch ($data['tab']) {
            // display only tabs
            case 'info':
            case 'preview':
            case 'help':
            case 'status':
                // fall through
                $invalid['phase'] = 1;
            break;
            
            // form input tabs
            case 'config':
                if ($isadmin) {
                    if (!xarVarFetch('instance_name', 'pre:trim:str:1:',
                        $name, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_title', 'pre:trim:str:0:',
                        $title, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_state', 'int:0:4',
                        $state, null, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_expire', 'pre:trim:str:0:20',
                        $expire, 0, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_expire_reset', 'checkbox',
                        $expire_reset, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_box_template', 'pre:trim:str:0:',
                        $box_template, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_block_template', 'pre:trim:str:0:',
                        $block_template, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_groups', 'array',
                        $groups, array(), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('instance_attachgroup', 'int:1:',
                        $attachgroup, null, XARVAR_NOT_REQUIRED)) return;
                    
                    if (empty($name) || strlen($name) > 64) {
                        $invalid['name'] = xarML('Name must be a string between 1 and 64 characters long');
                    } elseif (!preg_match('!^([a-z0-9_])*$!', $name)) {
                        $invalid['name'] = xarML('Name can only contain the characters [a-z0-9_]');
                    } elseif ($name != $instance['name']) {
                        $check = xarMod::apiFunc('blocks', 'instances', 'getitem',
                            array('name' => $name));
                        if ($check && $check['block_id'] != $instance['block_id'])
                            $invalid['name'] = xarML('A block instance named "#(1)" already exists', $name);
                    }
                    
                    if (!empty($title) && strlen($title) > 254)
                        $invalid['title'] = xarML('Title must be a string no more than 254 characters long');
                    

                    if (!isset($instance_states[$state]))
                        $invalid['state'] = xarML('Unknown block instance state');
                    
                    if (!empty($box_template) && strlen($box_template) > 127)
                        $invalid['templates'] = xarML('Template must be a string no more than 127 characters long');
                    if (!empty($block_template) && strlen($block_template) > 127)
                        $invalid['templates'] = xarML('Template must be a string no more than 127 characters long');

                    $instance_groups = array();
                    if (!empty($groups)) {
                        foreach ($groups as $group_id => $tpls) {
                            if (!isset($block_groups[$group_id])) continue;
                            if (!is_string($tpls['box_template'])) {
                                $tpls['box_template'] = '';
                                $badtemplates = true;
                            } elseif (strlen($tpls['box_template']) > 127) {
                                $badtemplates = true;
                            }
                            if (!is_string($tpls['block_template'])) {
                                $tpls['block_template'] = '';
                                $badtemplates = true;
                            } elseif (strlen($tpls['block_template']) > 127) {
                                $badtemplates = true;
                            }
                            $instance_groups[$group_id] = $tpls;
                        }
                        if (!empty($badtemplates)) 
                            $invalid['templates'] = xarML('Template must be a string no more than 127 characters long');
                    }

                    if (!empty($attachgroup)) {
                        if (!isset($block_groups[$attachgroup])) {
                            $invalid['attachgroup'] = xarML('Specified block group does not exist');
                        } elseif (isset($instance_groups[$attachgroup])) {
                            $invalid['attachgroup'] = xarML('Instance is already a member of #(1) group', $blockgroups[$attachgroup]['name']);
                        }
                        if (!empty($invalid['attachgroup']))
                            $attachgroup = null;
                    }
                    
                    $instance['name'] = $name;
                    $instance['title'] = $title;
                    $instance['state'] = $state;
                }

                $isvalid = xarBlock::hasMethod($block, 'checkmodify', true) ? $block->checkmodify() : true;
                if (!$isvalid)
                    $invalid['modify_update'] = xarML('Input failed validation for modify method');
                if (empty($invalid)) {
                    $result = $block->update();
                    if (!$result) return;
                    if (isset($result['content']))
                        $block->setContent($result['content']);
                    if ($isadmin) {
                        if (!empty($expire) && !$expire_reset) {
                            // convert expire time from dd:hh:mm:ss format to an integer
                            $expire = xarMod::apiFunc('blocks', 'user', 'convertseconds', 
                                array('direction' => 'to', 'starttime' => $expire, 'countdays' => true));
                            // block expires in now + expire time
                            $expire += time();
                            $block->setExpire($expire);
                        } elseif ($expire_reset) {
                            $block->setExpire(0);
                        }  
                        $block->setBoxTemplate($box_template);
                        $block->setBlockTemplate($block_template);
                        if (!empty($attachgroup)) 
                            $instance_groups[$attachgroup] = array(
                                'box_template' => '', 'block_template' => '',
                            );
                        $old_groups = $block->getGroups();
                        foreach ($instance_groups as $group_id => $tpls) {
                            if (!empty($tpls['detach'])) {
                                $block->detachGroup($group_id);
                                $group_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_groups[$group_id]);
                                $group_block->detachInstance($instance['block_id']);
                            } else {
                                $block->attachGroup($group_id, $tpls['box_template'], $tpls['block_template']);
                                if (isset($old_groups[$group_id])) continue;                                
                                $group_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_groups[$group_id]);
                                $group_block->attachInstance($instance['block_id']);
                            }
                            $group_update = array(
                                'block_id' => $group_id,
                                'content' => $group_block->storeContent(),
                            );
                            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $group_update)) return;
                            unset($group_block, $group_update);
                        }
             
                    }
                    $instance['content'] = $block->storeContent();
                } else {
                    // invalid data, pass what we got back to the form...
                    if ($isadmin) {
                        $instance['content']['box_template'] = $box_template;
                        $instance['content']['block_template'] = $block_template;
                        $instance['content']['instance_groups'] = $instance_groups;
                        $instance['attachgroup'] = $attachgroup;
                    }
                }

            break;
            
            case 'caching':

                if (!xarVarFetch('instance_nocache', 'checkbox', 
                    $nocache, false, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('instance_pageshared', 'checkbox', 
                    $pageshared, false, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('instance_usershared', 'int:0:2', 
                    $usershared, 0, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('instance_cacheexpire', 'str:1:', 
                    $cacheexpire, NULL, XARVAR_NOT_REQUIRED)) return;
                
                // convert cacheexpire from hh:mm:ss format to an integer
                if (!empty($cacheexpire)) 
                    $cacheexpire = xarMod::apiFunc('blocks', 'user', 'convertseconds', 
                        array('direction' => 'to', 'starttime' => $cacheexpire));
                
                $instance['content']['nocache'] = $nocache;
                $instance['content']['pageshared'] = $pageshared;
                $instance['content']['usershared'] = $usershared;
                $instance['content']['cacheexpire'] = $cacheexpire;
                              
            break;
            
            case 'access':

                $isvalid = $accessproperty->checkInput('instance_display_access');
                $instance['content']['display_access'] = $accessproperty->value;
                $isvalid = $accessproperty->checkInput('instance_modify_access');
                $instance['content']['modify_access'] = $accessproperty->value;
                $isvalid = $accessproperty->checkInput('instance_delete_access');
                $instance['content']['delete_access'] = $accessproperty->value;
                            
            break;
            
            default:

                $checkmethod = 'check' . $data['tab'];
                $updatemethod = 'update' . $data['tab'];
                $isvalid = xarBlock::hasMethod($block, $checkmethod, true) ? $block->$checkmethod() : true;
                if (!$isvalid)
                    $invalid['custom_update'] = xarML('Input failed validation for custom method #(1)', $updatemethod);

                if (empty($invalid) && xarBlock::hasMethod($block, $updatemethod, true)) {
                    $result = $block->$updatemethod(); 
                    if (!$result) return;
                    if (isset($result['content']))
                        $block->setContent($result['content']);
                    $instance['content'] = $block->storeContent();
                    if (!empty($result['return_url']))
                        $return_url = $result['return_url'];         
                }
                      
            break;            
        }
        if (empty($invalid)) {
            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $instance)) return;
            if (!xarVarFetch('return_url', 'pre:trim:str:1:',
                $return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'modify_instance',
                    array('block_id' => $instance['block_id'], 'tab' => $data['tab']));
            xarController::redirect($return_url);        
        } 
        // pass the invalid messages back to the form 
        $data['invalid'] = $invalid;       
    }


    // form phase     
    switch ($data['tab']) {
        // display only tabs
        case 'info':

            // $instance already gives us most of what we need 
            // get params that can be set in block tag attributes 
            // @fixme: this should be a method of the basicblock/blocktype class
            // @todo: have the method return better definitions (data type hint, validation)
            $type_params = array();
            $content = $block->getContent();
            if (!empty($content)) {
                foreach ($content as $k => $v) {
                    $datatype = gettype($v);
                    switch ($datatype) {
                        case 'string':
                            $value = '"'.$v.'"';
                        break;
                        case 'float':
                        case 'double':
                        case 'integer':
                        case 'NULL':
                            $value = $v;
                        break;
                        case 'boolean':
                            $value = $v ? 'true' : 'false';
                        break;
                        default:
                            continue 2;
                        break;
                    }
                    $type_params[$k] = array(
                        'attribute' => $k,
                        'datatype' => $datatype,
                        'default' => $value,
                    );
                }
            }
            $data['type_params'] = $type_params;        
        break;
        case 'preview':
            if (!empty($instance['type_info']['show_preview']))
                $data['preview_output'] = xarBlock::guiMethod($block, 'display');
        break;
        
        case 'help':
            if (!empty($instance['type_info']['show_help']))
                $data['help_output'] = xarBlock::guiMethod($block, 'help');            
        break;
        
        case 'status':
            // show status errors 
        break;

        // form input tabs
        case 'config':
            try {
                $data['config_output'] = xarBlock::guiMethod($block, 'modify');
            } catch (FileNotFoundException $e) {
                // must be missing the template (we already got the class file) 
                // if the block class didn't declare a modify method, that's ok
                if (!xarBlock::hasMethod($block, 'modify', true)) {
                    $data['config_output'] = '';
                } else {
                    throw $e;
                }
            } catch (Exception $e) {
                throw $e;
            }
            
            if ($isadmin) {
                if (!empty($instance['content']['expire'])) {
                    $now = time();
                    $soon = $instance['content']['expire'] - $now ;
                    $instance['expirein'] = $soon;
                    if ($now > $instance['content']['expire'] && $instance['content']['expire'] != 0) {
                        $instance['expire'] = 0;
                    } else {
                        $instance['expire'] = $instance['content']['expire'];
                    }
                } else {
                   $instance['expire'] = 0;
                   $instance['expirein'] = 0;
                }
                $groups = array();
                if (!empty($instance['content']['instance_groups'])) {
                    foreach ($block_groups as $group_id => $group) {
                        if (!isset($instance['content']['instance_groups'][$group_id])) continue;
                        $group += $instance['content']['instance_groups'][$group_id];
                        $group['detach'] = !empty($group['detach']);
                        $groups[$group_id] = $group;
                    }
                }
                $instance['groups'] = $groups;
                $group_options = array();
                foreach ($block_groups as $id => $option) {
                    if (isset($groups[$id])) continue;
                    $group_options[$id] = array(
                        'id' => $id,
                        'name' => $option['name'],
                    );
                }
                $data['group_options'] = $group_options;
                if (!isset($instance['attachgroup']))
                    $instance['attachgroup'] = null;                       
            }          
            
        break;
        
        case 'caching':
            // convert expire time to hh:mm:ss format for display
            if (!empty($instance['content']['cacheexpire'])) 
                $instance['content']['cacheexpire'] = xarMod::apiFunc('blocks', 'user', 'convertseconds',
                    array('direction' => 'from', 'starttime' => $instance['content']['cacheexpire']));
            $data['usershared_options'] = array(
                array('id' => 0, 'name' => xarML('No Sharing')),
                array('id' => 1, 'name' => xarML('Group Members')),
                array('id' => 2, 'name' => xarML('All Users')),
            );        
        break;
        
        case 'access':
        
        break;
                
        default:
            if (xarBlock::hasMethod($block, $data['tab'], true)) 
                $data['custom_output'] = xarBlock::guiMethod($block, $data['tab']);        
        break;
    }
    
    $data['instance'] = $instance;
    $data['instance_states'] = $instance_states;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $data['isadmin'] = $isadmin;
    $data['blocktabs'] = array(
        'info' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'info')),
            'label' => xarML('Info'),
            'title' => xarML('View block type information'),
        ));
    if ($data['tab'] != 'status') {
        if ($canmodify) {   
            $data['blocktabs'] += array(
                'config' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'config')),
                    'label' => xarML('Config'),
                    'title' => xarML('Modify configuration of this block instance'),
                ),
            );
        }
        if ($isadmin) {
            $data['blocktabs'] += array(
                'caching' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'caching')),
                    'label' => xarML('Caching'),
                    'title' => xarML('Modify caching configuration for this block instance'),
                ),
                'access' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'access')),
                    'label' => xarML('Access'),
                    'title' => xarML('Modify access configuration for this block instance'),
                ),        
            );
        }
        
        if (!empty($instance['type_info']['show_preview'])) {
            $data['blocktabs'] += array(
                'preview' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'preview')),
                    'label' => xarML('Preview'),
                    'title' => xarML('Show a preview of this block instance'),
                ),
            );
        }                

        if (!empty($instance['type_info']['show_help'])) {
            $data['blocktabs']['help'] = array(
                'url' => xarServer::getCurrentURL(array('tab' => 'help')),
                'label' => xarML('Help'),
                'title' => xarML('View help information about this block type'),
            );
        }    
    }    

    return $data;
}
?>
