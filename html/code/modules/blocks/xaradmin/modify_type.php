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
function blocks_admin_modify_type(Array $args=array())
{
    if (!xarSecurityCheck('ManageBlocks')) return;
    
    if (!xarVarFetch('type_id', 'int:1:',
        $type_id, null, XARVAR_DONT_SET)) return;
        
    if (!isset($type_id)) {
        $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
        $vars = array('type_id', 'blocks', 'admin', 'modify_type');
        throw new EmptyParameterException($vars, $msg);
    }
    
    $type = xarMod::apiFunc('blocks', 'types', 'getitem',
        array('type_id' => $type_id));
    
    if (!$type) {
        $msg = 'Block type id "#(1)" does not exist';
        $vars = array($type_id);
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

    // show the status warning if the type isn't active
    if ($type['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
        $interface = 'display';
        $method = 'status';
        $phase = 'display';
    } else {
        // admins only beyond the display interface methods 
        if ($interface != 'display')
            if (!xarSecurityCheck('AdminBlocks')) return;    
        // get the block object and load the interface
        $block = xarBlock::getObject($type, $interface);
    }
    
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
                                $invalid['update'] = xarML('Failed updating block type configuration');
                        } else {
                            $invalid['check'] = xarML('Failed validating block type form input');
                        }
                        // fetch block subsystem configuration 
                        if (!xarVarFetch('type_block_template', 'pre:trim:str:1:127',
                            $block_template, null, XARVAR_NOT_REQUIRED)) return;
                        if (!xarVarFetch('type_box_template', 'pre:trim:str:1:127',
                            $box_template, null, XARVAR_NOT_REQUIRED)) return;
                        // update block configuration 
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
                            $block->setBlockTemplate($block_template);
                            $block->setBoxTemplate($box_template);
                        }                           
                        // fall through                  
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
                                    $invalid['update'] = xarML('Failed updating block type configuration');
                            }
                        } else {
                            $invalid['check'] = xarML('Failed validating block type form input');
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
                        // fall through  
                    break;
                }
            
            break;
            case 'caching':
                if (!xarVarFetch('type_nocache', 'checkbox',
                    $nocache, false, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('type_pageshared', 'checkbox',
                    $pageshared, false, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('type_usershared', 'int:0:2',
                    $usershared, 0, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('type_cacheexpire', 'str:1:',
                    $cacheexpire, null, XARVAR_NOT_REQUIRED)) return;
                
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
                    $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
                    $isvalid = $accessproperty->checkInput('type_add_access');
                    $block->setAccess('add', $accessproperty->value);
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
        $type['type_info'] = $block->storeContent();
        // valid input, go ahead and update the block type info 
        if (empty($invalid)) {
        
            if (!xarMod::apiFunc('blocks', 'types', 'updateitem', $type)) return;
            
            if (!xarVarFetch('return_url', 'pre:trim:str:1:',
                $return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'modify_type',
                    array(
                        'type_id' => $type['type_id'],
                        'interface' => $interface,
                        'block_method' => $method,
                    ));
            xarController::redirect($return_url);
        }
        $data['invalid'] = $invalid;
          
    }


    // handle display phase
    switch ($interface) {
        case 'display':
            if (empty($method))
                $method = 'info';
            switch ($method) {
                case 'info':
                    // $type already gives us most of what we need 
                    // get params that can be set in block tag attributes 
                    // @todo: this should be a method of the basicblock/blocktype class
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
                                    $value = $v ? '1' : '0';
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
                    
                    // show additional info if supplied by block type
                    if (xarBlock::hasMethod($block, 'info', true))
                        $data['type_output'] = xarBlock::guiMethod($block, 'info');
                    
                break;
                case 'preview':
                    // show using preview method if supplied by block type... 
                    if (xarBlock::hasMethod($block, 'preview', true)) {
                        $data['type_output'] = xarBlock::guiMethod($block, 'preview');
                    } 
                    // or using display method otherwise...                    
                    else {
                        $data['type_output'] = xarBlock::guiMethod($block, 'display');
                    }
                
                break;
                case 'help':
                    // show help info if supplied by block type
                    if (xarBlock::hasMethod($block, 'help', true))
                        $data['type_output'] = xarBlock::guiMethod($block, 'help');
                break;
                case 'status':
                
                break;
                default:
                    // show custom info if supplied by block type
                    if (xarBlock::hasMethod($block, $method, true))
                        $data['type_output'] = xarBlock::guiMethod($block, $method);
                break;
            }
        break;
        case 'config':
            if (empty($method))
                $method = 'config';
            switch ($method) {
                case 'config':
                    try {
                        $data['type_output'] = xarBlock::guiMethod($block, 'configmodify', 'config-'. $block->type);
                    } catch (FunctionNotFoundException $e) {
                        try {
                            $data['type_output'] = xarBlock::guiMethod($block, 'modify');
                        } catch (FunctionNotFoundException $f) {
                            $data['type_output'] = '';
                        } catch (FileNotFoundException $f) {
                            $data['type_output'] = '';
                        } catch (Exception $f) {
                            throw $f;
                        }
                    } catch (Exception $e) {
                        throw $e;
                    }
                break;
                default:
                    // show custom configuration supplied by block type
                    $modify_method = $method.'modify';
                    $data['type_output'] = xarBlock::guiMethod($block, $modify_method, $method.'-'.$block->type);
                break;
            }           
        break;
        case 'caching':
            // convert expire time to hh:mm:ss format for display
            if (!empty($type['type_info']['cacheexpire'])) 
                $type['type_info']['cacheexpire'] = xarMod::apiFunc('blocks', 'user', 'convertseconds', 
                    array('direction' => 'from', 'starttime' => $type['type_info']['cacheexpire']));

            $data['usershared_options'] = array(
                array('id' => 0, 'name' => xarML('No Sharing')),
                array('id' => 1, 'name' => xarML('Group Members')),
                array('id' => 2, 'name' => xarML('All Users')),
            );
            // show additional caching info if supplied by block type
            if (xarBlock::hasMethod($block, 'cachingmodify', true))
                $data['type_output'] = xarBlock::guiMethod($block, 'cachingmodify', 'caching-'.$block->type);
        
        break;
        case 'access':
            // show additional access info if supplied by block type
            if (xarBlock::hasMethod($block, 'accessmodify', true))
                $data['type_output'] = xarBlock::guiMethod($block, 'accessmodify', 'access-'.$block->type);        
        break;
        default:
            // block type may supply a custom interface and methods 
            if (empty($method))
                $method = $interface;
            $modify_method = $method.'modify';
            $data['type_output'] = xarBlock::guiMethod($block, $modify_method, $method.'-'.$block->type);
        break;
    }

    $data['type'] = $type;
    $data['interface'] = $interface;
    $data['method'] = $method;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $interfaces = array();
    $interfaces[] = array(
        'url' => xarServer::getCurrentURL(array('interface' => 'display', 'block_method' => null)),
        'label' => xarML('Info'),
        'title' => xarML('Display information about this block type'),
        'active' => ($interface == 'display' && $method == 'info'),
    );
    if ($interface != 'display' || $method != 'status') {
        if (xarSecurityCheck('AdminBlocks', 0)) {
            $interfaces[] = array(
                'url' => xarServer::getCurrentURL(array('interface' => 'config', 'block_method' => null)),
                'label' => xarML('Config'),
                'title' => xarML('Modify default configuration for this block type'),
                'active' => ($interface == 'config'),
            ); 
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