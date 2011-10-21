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
    
    $blockinfo = xarMod::apiFunc('blocks', 'instances', 'getitem',
        array('block_id' => $block_id));
    
    if (!$blockinfo) {
        $msg = 'Block instance id "#(1)" does not exist';
        $vars = array($block_id);
        throw new IdNotFoundException($vars, $msg);
    }

    $data = array();

    // determine the interface, method and phase 
    if (!xarVarFetch('interface', 'pre:trim:lower:str:1:',
        $interface, 'display', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_method', 'pre:trim:lower:str:1:',
        $method, null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:str:1:',
        $phase, 'display', XARVAR_NOT_REQUIRED)) return;

    // admin access is needed for some operations 
    $isadmin = xarSecurityCheck('',0,'Block',"$blockinfo[type]:$blockinfo[name]:$blockinfo[block_id]",$blockinfo['module'],'',0,800);

    // show the status warning if the type isn't active
    if ($blockinfo['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
        $interface = 'display';
        $method = 'status';
        $phase = 'display';
    } else {
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        // check modify access 
        if ($isadmin) {
            $canmodify = true;
        } else {
            $args = array(
                'module' => $blockinfo['module'],
                'component' => 'Block',
                'instance' => "$blockinfo[type]:$blockinfo[name]:$blockinfo[block_id]",
                'group' => $blockinfo['content']['modify_access']['group'],
                'level' => $blockinfo['content']['modify_access']['level'],
            );
            $canmodify = $accessproperty->check($args);
        }
        switch ($interface) {
            case 'display':
                if (empty($method))
                    $method = 'info';
                $phase = 'display';
            break;
            case 'caching':
            case 'access':
                if (!$isadmin)
                    return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
                $method = $interface;
            case 'config':
            default:
                if (!$canmodify)
                    return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
                if (empty($method))
                    $method = $interface;
            break;
                    
        }          
        // get the block object and load the interface
        $block = xarBlock::getObject($blockinfo, $interface);

        $block_groups = xarMod::apiFunc('blocks', 'instances', 'getitems',
            array('type_category' => 'group',));

        // if block group, get instances attached to it    
        if ($block->type_category == 'group') {
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

    $instance_states = xarMod::apiFunc('blocks', 'instances', 'getstates');
    $type_states = xarMod::apiFunc('blocks', 'types', 'getstates');

    // handle update phase
    if ($phase == 'update') {
        $invalid = array();
        switch ($interface) {
            case 'display':
                $invalid['phase'] = xarML('Update phase not supported in display interface');
                // fall through to display phase 
                $phase = 'display';
            break;
            case 'config':
                if (empty($method))
                    $method = 'config';
                switch ($method) {
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
                            } elseif ($name != $blockinfo['name']) {
                                $check = xarMod::apiFunc('blocks', 'instances', 'getitem',
                                    array('name' => $name));
                                if ($check && $check['block_id'] != $blockinfo['block_id'])
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

                            $blockinfo_groups = array();
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
                                    $blockinfo_groups[$group_id] = $tpls;
                                }
                                if (!empty($badtemplates)) 
                                    $invalid['templates'] = xarML('Template must be a string no more than 127 characters long');
                            }

                            if (!empty($attachgroup)) {
                                if (!isset($block_groups[$attachgroup])) {
                                    $invalid['attachgroup'] = xarML('Specified block group does not exist');
                                } elseif (isset($blockinfo_groups[$attachgroup])) {
                                    $invalid['attachgroup'] = xarML('Instance is already a member of #(1) group', $blockgroups[$attachgroup]['name']);
                                }
                                if (!empty($invalid['attachgroup']))
                                    $attachgroup = null;
                            }
                    
                            $blockinfo['name'] = $name;
                            $blockinfo['title'] = $title;
                            $blockinfo['state'] = $state;
                        }

                        // if the block type supplied a validation method, use it                         
                        if (xarBlock::hasMethod($block, 'configcheck', true)) {
                            $isvalid = $block->configcheck();
                        } elseif (xarBlock::hasMethod($block, 'checkmodify', true)) {
                            $isvalid = $block->checkmodify();
                        } else {
                            $isvalid = true;
                        }
                        // attempt to update the block type configuration 
                        if ($isvalid) {
                            if (xarBlock::hasMethod($block, 'configupdate', true)) {
                                $result = $block->configupdate();
                            } elseif (xarBlock::hasMethod($block, 'update', true)) {
                                $result = $block->update();
                            }
                            if (isset($result) && $result == false)
                                $invalid['update'] = xarML('Failed updating block instance configuration');
                        } else {
                            $invalid['check'] = xarML('Failed validating block instance form input');
                        }

                        if (empty($invalid)) {

                            if (!xarSecConfirmAuthKey())
                                return xarTpl::module('privileges', 'user', 'errors', 
                                    array('layout' => 'bad_author'));

                            if (isset($result) && is_array($result)) {
                                if (!empty($result['content']))
                                    $block->setContent($result['content']);
                                if (!empty($result['return_url']))
                                    $return_url = $result['return_url'];
                            }
                            
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
                                    $blockinfo_groups[$attachgroup] = array(
                                        'box_template' => '', 'block_template' => '',
                                    );
                                $old_groups = $block->getGroups();
                                foreach ($blockinfo_groups as $group_id => $tpls) {
                                    if (!empty($tpls['detach'])) {
                                        $block->detachGroup($group_id);
                                        $group_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_groups[$group_id]);
                                        $group_block->detachInstance($blockinfo['block_id']);
                                    } else {
                                        $block->attachGroup($group_id, $tpls['box_template'], $tpls['block_template']);
                                        if (isset($old_groups[$group_id])) continue;                                
                                        $group_block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $block_groups[$group_id]);
                                        $group_block->attachInstance($blockinfo['block_id']);
                                    }
                                    $group_update = array(
                                        'block_id' => $group_id,
                                        'content' => $group_block->storeContent(),
                                    );
                                    if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $group_update)) return;
                                    unset($group_block, $group_update);
                                }
             
                            }
                        } else {
                            // invalid data, pass what we got back to the form...
                            if ($isadmin) {
                                $blockinfo['attachgroup'] = $attachgroup;
                            }
                        }                                                
                    
                    break;
                    default:
                        // block type supplied a custom config interface method 
                        $check_method = $method.'check';
                        $isvalid = xarBlock::hasMethod($block, $check_method, true) 
                            ? $block->$check_method() : true;
                        if ($isvalid) {
                            $update_method = $method.'update';
                            if (xarBlock::hasMethod($block, $update_method, true)) {
                                $result = $block->$update_method();
                                if (empty($result))
                                    $invalid['update'] = xarML('Failed updating block instance configuration');
                            }
                        } else {
                            $invalid['check'] = xarML('Failed validating block instance form input');
                        }
                        // update block configuration 
                        if (empty($invalid)) {
                            if (!xarSecConfirmAuthKey())
                                return xarTpl::module('privileges', 'user', 'errors', 
                                    array('layout' => 'bad_author'));
                            if (!empty($result) && is_array($result)) {
                                if (!empty($result['content']))
                                    $block->setContent($result['content']);
                                if (!empty($result['return_url']))
                                    $return_url = $result['return_url'];
                            }
                        }                 
                    break;
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

                // block type may supply additional caching configuration
                $check_method = 'cachingcheck';
                $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                if ($isvalid) {
                    $update_method = 'cachingupdate';
                    if (xarBlock::hasMethod($block, $update_method, true)) {
                        $result = $block->$update_method();
                        if (empty($result))
                            $invalid['update'] = xarML('Failed updating block instance caching configuration');
                    }
                } else {
                    $invalid['check'] = xarML('Failed validating block instance caching form input');
                }
                
                // update block configuration 
                if (empty($invalid)) {
                    if (!xarSecConfirmAuthKey())
                        return xarTpl::module('privileges', 'user', 'errors', 
                            array('layout' => 'bad_author'));
                    if (!empty($result) && is_array($result)) {
                        if (!empty($result['content']))
                            $block->setContent($result['content']);
                        if (!empty($result['return_url']))
                            $return_url = $result['return_url'];
                    }
                    $block->setNoCache($nocache);
                    $block->setPageShared($pageshared);
                    $block->setUserShared($usershared);
                    $block->setCacheExpire($cacheexpire);
                }
            
            break;
            case 'access':

                // block type may supply additional access configuration
                $check_method = 'accesscheck';
                $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                if ($isvalid) {
                    $update_method = 'accessupdate';
                    if (xarBlock::hasMethod($block, $update_method, true)) {
                        $result = $block->$update_method();
                        if (empty($result))
                            $invalid['update'] = xarML('Failed updating block instance access configuration');
                    }
                } else {
                    $invalid['check'] = xarML('Failed validating block instance access form input');
                }            

                // update block configuration 
                if (empty($invalid)) {
                    if (!xarSecConfirmAuthKey())
                        return xarTpl::module('privileges', 'user', 'errors', 
                            array('layout' => 'bad_author'));
                    if (!empty($result) && is_array($result)) {
                        if (!empty($result['content']))
                            $block->setContent($result['content']);
                        if (!empty($result['return_url']))
                            $return_url = $result['return_url'];
                    }
                    $isvalid = $accessproperty->checkInput('instance_display_access');
                    $block->setAccess('display', $accessproperty->value);
                    $isvalid = $accessproperty->checkInput('instance_modify_access');
                    $block->setAccess('modify', $accessproperty->value);
                    $isvalid = $accessproperty->checkInput('instance_delete_access');
                    $block->setAccess('delete', $accessproperty->value);
                }
            
            break;
            default:

                if (empty($method))
                    $method = $interface;
                // block type may supply additional interfaces and methods
                $check_method = $method.'check';
                $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                if ($isvalid) {
                    $update_method = $method.'update';
                    if (xarBlock::hasMethod($block, $update_method, true)) {
                        $result = $block->$update_method();
                        if (empty($result))
                            $invalid['update'] = xarML('Failed updating block type caching configuration');
                    }
                } else {
                    $invalid['check'] = xarML('Failed validating block type caching form input');
                }            
                // update block configuration 
                if (empty($invalid)) {
                    if (!xarSecConfirmAuthKey())
                        return xarTpl::module('privileges', 'user', 'errors', 
                            array('layout' => 'bad_author'));
                    if (!empty($result) && is_array($result)) {
                        if (!empty($result['content']))
                            $block->setContent($result['content']);
                        if (!empty($result['return_url']))
                            $return_url = $result['return_url'];
                    }
                }   
            
            break;

        }
        $blockinfo['content'] = $block->storeContent();
        // valid input, go ahead and update the block instance info 
        if (empty($invalid)) {

            if (!xarMod::apiFunc('blocks', 'instances', 'updateitem', $blockinfo)) return;
            
            if (!xarVarFetch('return_url', 'pre:trim:str:1:',
                $return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'modify_instance',
                    array(
                        'block_id' => $blockinfo['block_id'],
                        'interface' => $interface,
                        'block_method' => $method,
                    ));
            xarController::redirect($return_url);
        }  
        // failed to validate, pass the invalid messages back to the form 
        $data['invalid'] = $invalid;
    }

    // handle display phase    
    switch ($interface) {
        case 'display':
            switch ($method) {
                case 'info':
                    // $blockinfo already gives us most of what we need 
                    // get params that can be set in block tag attributes 
                    // @todo: this should be a method of the basicblock/blocktype class
                    // @todo: have the method return better definitions (data type hint, validation)
                    $block_params = array();
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
                                    $value = $v ? '1' : '0';
                                break;
                                default:
                                    continue 2;
                                break;
                            }
                            $block_params[$k] = array(
                                'attribute' => $k,
                                'datatype' => $datatype,
                                'default' => $value,
                            );
                        }
                    }    
                    $data['block_params'] = $block_params;
                    
                    // show additional info if supplied by block type
                    if (xarBlock::hasMethod($block, 'info', true))
                        $data['block_output'] = xarBlock::guiMethod($block, 'info');
                
                break;
                case 'preview':
                    // show using preview method if supplied by block type... 
                    if (xarBlock::hasMethod($block, 'preview', true)) {
                        $data['block_output'] = xarBlock::guiMethod($block, 'preview');
                    } 
                    // or using display method otherwise...                    
                    else {
                        $data['block_output'] = xarBlock::guiMethod($block, 'display');
                    }              
                break;
                
                case 'help':
                    // show help info if supplied by block type
                    if (xarBlock::hasMethod($block, 'help', true))
                        $data['block_output'] = xarBlock::guiMethod($block, 'help');                 
                break;
                case 'status':
                
                break;
                default:
                    // show custom info if supplied by block type
                    if (xarBlock::hasMethod($block, $method, true))
                        $data['block_output'] = xarBlock::guiMethod($block, $method);                  
                break;
            }
        break;
        case 'config':
            switch ($method) {
                case 'config':
                    try {
                        $data['block_output'] = xarBlock::guiMethod($block, 'configmodify', 'config-'.$block->type);
                    } catch (FunctionNotFoundException $e) {
                        try {
                            $data['block_output'] = xarBlock::guiMethod($block, 'modify');
                        } catch (FunctionNotFoundException $f) {
                            $data['block_output'] = '';
                        } catch (FileNotFoundException $f) {
                            $data['block_output'] = '';
                        } catch (Exception $f) {
                            throw $f;
                        }
                    } catch (Exception $e) {
                        throw $e;
                    }
                    if ($isadmin) {
                        if (!empty($blockinfo['content']['expire'])) {
                            $now = time();
                            $soon = $blockinfo['content']['expire'] - $now ;
                            $blockinfo['expirein'] = $soon;
                            if ($now > $blockinfo['content']['expire'] && 
                                $blockinfo['content']['expire'] != 0) 
                            {
                                $blockinfo['expire'] = 0;
                            } else {
                                $blockinfo['expire'] = $blockinfo['content']['expire'];
                            }
                        } else {
                           $blockinfo['expire'] = 0;
                           $blockinfo['expirein'] = 0;
                        }
                        $groups = array();
                        if (!empty($blockinfo['content']['instance_groups'])) {
                            foreach ($block_groups as $group_id => $group) {
                                if (!isset($blockinfo['content']['instance_groups'][$group_id])) continue;
                                $group += $blockinfo['content']['instance_groups'][$group_id];
                                $group['detach'] = !empty($group['detach']);
                                $groups[$group_id] = $group;
                            }
                        }
                        $blockinfo['groups'] = $groups;
                        $group_options = array();
                        foreach ($block_groups as $id => $option) {
                            if (isset($groups[$id])) continue;
                            $group_options[$id] = array(
                                'id' => $id,
                                'name' => $option['name'],
                            );
                        }
                        $data['group_options'] = $group_options;
                        if (!isset($blockinfo['attachgroup']))
                            $blockinfo['attachgroup'] = null;                       
                    }     
                break;
                default:
                    // show custom configuration supplied by block type
                    $modify_method = $method.'modify';
                    $data['block_output'] = xarBlock::guiMethod($block, $modify_method, $method.'-'.$block->type);
                break;
            }
        break;
        case 'caching':
            // convert expire time to hh:mm:ss format for display
            if (!empty($blockinfo['content']['cacheexpire'])) 
                $blockinfo['content']['cacheexpire'] = xarMod::apiFunc('blocks', 'user', 'convertseconds',
                    array('direction' => 'from', 'starttime' => $blockinfo['content']['cacheexpire']));
            $data['usershared_options'] = array(
                array('id' => 0, 'name' => xarML('No Sharing')),
                array('id' => 1, 'name' => xarML('Group Members')),
                array('id' => 2, 'name' => xarML('All Users')),
            );                
        break;
        case 'access':
            // nothing special...
        break;
        default:
            if (empty($method))
                $method = $interface;
            // show custom configuration supplied by block type
            $modify_method = $method.'modify';
            $data['block_output'] = xarBlock::guiMethod($block, $modify_method, $method.'-'.$block->type);
        
        break;
    }
    
    $data['block'] = $blockinfo;
    $data['interface'] = $interface;
    $data['method'] = $method;
    $data['isadmin'] = $isadmin;
    $data['instance_states'] = $instance_states;
    $data['type_states'] = $type_states;
    $interfaces = array();
    $interfaces[] = array(
        'url' => xarServer::getCurrentURL(array('interface' => 'display', 'block_method' => null)),
        'label' => xarML('Info'),
        'title' => xarML('Display information about this block type'),
        'active' => ($interface == 'display' && $method == 'info'),
    );
    if ($interface != 'display' || $method != 'status') {
        if ($canmodify) {
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'config', 'block_method' => null)),
                'label' => xarML('Config'),
                'title' => xarML('Modify default configuration for this block type'),
                'active' => ($interface == 'config'),
            ); 
        }
        if ($isadmin) {
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'caching', 'block_method' => null)),
                'label' => xarML('Caching'),
                'title' => xarML('Modify default caching configuration for this block type'),
                'active' => ($interface == 'caching'),
            ); 
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'access', 'block_method' => null)),
                'label' => xarML('Access'),
                'title' => xarML('Modify default access configuration for this block type'),
                'active' => ($interface == 'access'),
            );
        }
        if ($block->show_preview) {
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'display', 'block_method' => 'preview')),
                'label' => xarML('Preview'),
                'title' => xarML('Show a preview of this block type'),
                'active' => ($interface == 'display' && $method == 'preview'),
            );
        }        
        if ($block->show_help) {
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'display', 'block_method' => 'help')),
                'label' => xarML('Help'),
                'title' => xarML('View block type help information'),
                'active' => ($interface == 'display' && $method == 'help'),
            );
        }
    }
    $data['interfaces'] = $interfaces;
        
    return $data;

}
?>