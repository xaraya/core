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

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $data['tab'], 'info', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:str:1:',
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;

    // show the status warning if the type isn't active
    if ($type['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
        $data['tab'] = 'status';
        $phase = 'form';
    }

    // only get the block instance if the type is active 
    if ($data['tab'] != 'status') {
        $filter = $type;
        switch ($data['tab']) {
            case 'info':
                // managers can see info
                $filter['method'] = 'modify';
            break;
            case 'preview':
                // managers can see preview
                $filter['method'] = 'display';
            break;
            case 'access':
            case 'caching':
            case 'config':
                // admins only here
                if (!xarSecurityCheck('AdminBlocks')) return;
                $filter['method'] = 'modify';
            break;
            default:
                // admins only here
                if (!xarSecurityCheck('AdminBlocks')) return;
                $filter['method'] = $data['tab'];
            break;
        }
    
        $block = xarMod::apiFunc('blocks', 'blocks', 'getobject', $filter);
    }

    
    // update phase
    if ($phase == 'update') {

        if (!xarSecConfirmAuthKey())
            return xarTpl::module('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        
        $update = array();   

        switch ($data['tab']) {
            case 'info':
            case 'preview':
            case 'status':
            case 'help':
            break;
            
            case 'config':

                if (!xarVarFetch('type_block_template', 'pre:trim:str:1:127',
                    $block_template, null, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('type_box_template', 'pre:trim:str:1:127',
                    $box_template, null, XARVAR_NOT_REQUIRED)) return;

                $isvalid = xarBlock::hasMethod($block, 'checkmodify', true) ? $block->checkmodify() : true;
                if ($isvalid) {
                    $result = $block->update();
                    if (!$result) return;
                    if (isset($result['content']))
                        $block->setContent($result['content']);

                    $block->setBlockTemplate($block_template);
                    $block->setBoxTemplate($box_template);

                    $update['type_info'] = $block->storeContent();       
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

                $block->setNoCache($nocache);
                $block->setPageShared($pageshared);
                $block->setUserShared($usershared);
                $block->setCacheExpire($cacheexpire);
                
                $update['type_info'] = $block->storeContent();       
            
            break;
            
            case 'access':
                
                $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
                $isvalid = $accessproperty->checkInput('type_add_access');
                $type_info = $block->storeContent();
                $type_info['add_access'] = $accessproperty->value;
                
                $update['type_info'] = $type_info;        

            break;
            
            default:
                $checkmethod = 'check' . $data['tab'];
                $isvalid = xarBlock::hasMethod($block, $checkmethod, true) ? $block->$checkmethod() : true;
                $updatemethod = 'update' . $data['tab'];
                if ($isvalid && xarBlock::hasMethod($block, $updatemethod, true)) {
                    $result = $block->$updatemethod();
                    if (!$result) return;
                    if (isset($result['content']))
                        $block->setContent($result['content']);
                    $type_info = $block->storeContent();
                    
                    $update['type_info'] = $type_info;        

                    if (!empty($result['return_url']))
                        $return_url = $result['return_url'];         
                }
            break;                
        }
        
        if (!empty($update)) {
            $update['type_id'] = $type['type_id'];
            if (!xarMod::apiFunc('blocks', 'types', 'updateitem', $update)) return;
            
            if (!xarVarFetch('return_url', 'pre:trim:str:1:',
                $return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url))
                $return_url = xarModURL('blocks', 'admin', 'modify_type',
                    array(
                        'type_id' => $type['type_id'],
                        'tab' => $data['tab'],
                    ));
            xarController::redirect($return_url);
        }
        // fall through to display phase...   
    }

    // display phase    
    switch ($data['tab']) {
        
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
        break;
        
        case 'config':
            try {
                $data['type_output'] = xarBlock::guiMethod($block, 'modify');
            } catch (FileNotFoundException $e) {
                // must be missing the template (we already got the class file) 
                // if the block class didn't declare a modify method, that's ok
                if (!xarBlock::hasMethod($block, 'modify', true)) {
                    $data['type_output'] = '';
                } else {
                    throw $e;
                }
            } catch (Exception $e) {
                throw $e;
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
        break;
        
        case 'access':
        
        break;
        
        case 'preview':
            if (!empty($type['type_info']['show_preview']))
                $data['type_output'] = xarBlock::guiMethod($block, 'display');
        break;
        
        case 'help':
            if (!empty($type['type_info']['show_help']))
                $data['type_output'] = xarBlock::guiMethod($block, 'help');

        case 'status':
        
        break;
                
        default:
            if (xarBlock::hasMethod($block, $data['tab'], true))
                $data['type_output'] = xarBlock::guiMethod($block, $data['tab']);
        break;

    }

    $data['type'] = $type;
    $data['type_states'] = xarMod::apiFunc('blocks', 'types', 'getstates');
    $data['blocktabs'] = array(
        'info' => array(
            'url' => xarServer::getCurrentURL(array('tab' => 'info')),
            'label' => xarML('Info'),
            'title' => xarML('View block type information'),
        ));
    if ($data['tab'] != 'status') {
        if (xarSecurityCheck('AdminBlocks', 0)) {   
            $data['blocktabs'] += array(
                'config' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'config')),
                    'label' => xarML('Config'),
                    'title' => xarML('Modify default configuration for this block type'),
                ),
                'caching' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'caching')),
                    'label' => xarML('Caching'),
                'title' => xarML('Modify default caching configuration for this block type'),
                ),
                'access' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'access')),
                    'label' => xarML('Access'),
                    'title' => xarML('Modify default access configuration for this block type'),
                ),        
            );
        }

        if (!empty($type['type_info']['show_preview'])) {
            $data['blocktabs'] += array(
                'preview' => array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'preview')),
                    'label' => xarML('Preview'),
                    'title' => xarML('Show a preview of this block type'),
                ),
            );
        }        

        if (!empty($type['type_info']['show_help'])) {
            $data['blocktabs']['help'] = array(
                'url' => xarServer::getCurrentURL(array('tab' => 'help')),
                'label' => xarML('Help'),
                'title' => xarML('View block type help information'),
            );
        }    
    }
    
    return $data;
        
}
?>