<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * update attributes of a block instance
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @param $args['tab'] the current update phase
 * @param $args['bid'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block (deprecated)
 * @param $args['groups'] optional array of group memberships
 * @param $args['template'] the template of the block instance
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @return boolean true on success, false on failure
 */
function blocks_admin_update_instance()
{
    if (!xarVarFetch('bid', 'int:1:', $bid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:', $tab, 'config', XARVAR_NOT_REQUIRED)) return;

    // Security
    if (empty($bid)) return xarController::notFound();
    if (!xarSecurityCheck('EditBlocks', 0, 'Instance')) {return;}

    if (!xarSecConfirmAuthKey())
        return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));

    // Get the instance details.
    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));

    $adminaccess = xarSecurityCheck('',0,'Block',$instance['type'] . ":" . $instance['name'] . ":" . "$instance[bid]",$instance['module'],'',0,800);

    // Load the block file
    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array('module' => $instance['module'], 'type' => $instance['type'], 'func' => 'update'))) return;

    // cascading block files - order is method specific, admin specific, block specific
    $to_check = array();
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'BlockUpdate';   // from eg menu_update.php
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'BlockAdmin';    // from eg menu_admin.php
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'Block';         // from eg menu.php

    // Block type properties config
    foreach ($to_check as $className) {
        // @FIXME: class name should be unique
        if (class_exists($className)) {
            // instantiate the block instance using the first class we find
            $block = new $className($instance);
            break;
        }
    }
    // make sure we instantiated a block,
    if (empty($block)) {
        // return classname not found (this is always class [$type]Block)
        throw new ClassNotFoundException($className);
    }

    // checkAccess (against modify access)
    if (!$block->checkAccess('modify')) {
        $instance['allowaccess'] = false;
        if (!empty($block->modify_access) && $block->modify_access['failure'])
            return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));
    } else {
        $instance['allowaccess'] = true;
    }
    // use the instance details for blockinfo
    if (empty($blockinfo) || !is_array($blockinfo)) {
        $blockinfo = $instance;
    }

    switch ($tab) {
        case 'config':

            if ($instance['allowaccess']) {
                $blockinfo = $block->update();
                // @FIXME: the update method must return an array of blockinfo
                // need to raise an exception here if it doesn't
            }
            // only admins can modify block properties
            if ($adminaccess) {
                // Block properties
                if (!xarVarFetch('block_name', 'pre:lower:ftoken:field:Name:passthru:str:1:100', $name)) {return;}
                if (!xarVarFetch('block_title', 'str:1:255', $title, '', XARVAR_NOT_REQUIRED)) {return;}
                if (!xarVarFetch('block_state', 'int:0:4', $state)) {return;}
                // @FIXME: This is deprecated, needs removing from block_instance table
                if (!xarVarFetch('block_refresh', 'int:0:1', $refresh, 0, XARVAR_NOT_REQUIRED)) {return;}

                // Block instance template
                if (!xarVarFetch('block_template_outer', 'pre:trim:lower:str:1:', $template_outer, null, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('block_template_inner', 'pre:trim:lower:str:1:', $template_inner, null, XARVAR_NOT_REQUIRED)) return;

                // concatenate outer and inner templates for storage
                $block_template = !empty($template_outer) ? $template_outer : '';
                if (!empty($template_inner)) $block_template .= ';'.$template_inner;

                // Block expiration setting
                if (!xarVarFetch('block_expire', 'str:1', $expire, '', XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('block_expire_reset', 'checkbox', $expire_reset, false, XARVAR_NOT_REQUIRED)) return;
                if (!empty($expire) && !$expire_reset) {
                    // convert expire time from dd:hh:mm:ss format to an integer
                    $expire = xarModAPIFunc('blocks', 'user', 'convertseconds', array('direction' => 'to', 'starttime' => $expire, 'countdays' => true));
                    // block expires in now + expire time
                    $expire += time();
                    $blockinfo['content']['expire'] = $expire;
                } elseif ($expire_reset) {
                    $blockinfo['content']['expire'] = 0;
                } else {
                    $blockinfo['content']['expire'] = isset($blockinfo['expire']) ? $blockinfo['expire'] : 0;
                }

                // Block group properties
                if (!xarVarFetch('block_groups', 'keylist:id;checkbox', $block_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
                if (!xarVarFetch('block_new_group', 'id', $block_new_group, 0, XARVAR_NOT_REQUIRED)) {return;}
                if (!xarVarFetch('block_remove_groups', 'keylist:id;checkbox', $block_remove_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
                if (!xarVarFetch('group_templates', 'keylist', $group_templates, array(), XARVAR_NOT_REQUIRED)) {return;}

                // If the name is being changed, then check the new name has not already been used.
                if ($blockinfo['name'] != $name) {
                    $checkname = xarMod::apiFunc('blocks', 'user', 'get', array('name' => $name));
                    if (!empty($checkname)) {
                        throw new DuplicateException(array('block',$name));
                    }
                }
                // add in the new values
                $blockinfo['name'] = $name;
                $blockinfo['title'] = $title;
                $blockinfo['template'] = $block_template;
                $blockinfo['refresh'] = $refresh;
                $blockinfo['state'] = $state;
                // Pick up the block instance groups and templates.
                $groups = array();
                foreach($block_groups as $id => $block_group) {
                    // Set the block group so long as the 'remove' checkbox is not set.
                    if (!isset($block_remove_groups[$id]) || $block_remove_groups[$id] == false) {
                        // concatenate outer and inner group instance templates
                        $group_template = $group_templates[$id]['outer'];
                        if (!empty($group_templates[$id]['inner'])) $group_template .= ';' . $group_templates[$id]['inner'];
                        $groups[] = array(
                            'id' => $id,
                            'template' => $group_template,
                        );
                    }
                }
                // The block was added to a new block group using the drop-down.
                if (!empty($block_new_group)) {
                    $groups[] = array(
                        'id' => $block_new_group,
                        'template' => null
                    );
                }
                $blockinfo['groups'] = $groups;
            }

        break;

        case 'caching':

            // @CHECKME: only admins can modify cache settings?
            if (!$adminaccess)
                return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));

            if (!xarVarFetch('block_nocache', 'checkbox', $nocache, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('block_pageshared', 'checkbox', $pageshared, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('block_usershared', 'int:0:2', $usershared, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('block_cacheexpire', 'str:1:', $cacheexpire, NULL, XARVAR_NOT_REQUIRED)) return;

            // convert cacheexpire from hh:mm:ss format to an integer
            if (!empty($cacheexpire)) {
                $cacheexpire = xarModAPIFunc('blocks', 'user', 'convertseconds', array('direction' => 'to', 'starttime' => $cacheexpire));
            }

            $blockinfo['content']['nocache'] = $nocache;
            $blockinfo['content']['pageshared'] = $pageshared;
            $blockinfo['content']['usershared'] = $usershared;
            $blockinfo['content']['cacheexpire'] = $cacheexpire;

            if (!xarModAPIFunc('blocks', 'admin', 'create_cacheinstance',
                array(
                    'bid' => $bid,
                    'nocache' => $nocache,
                    'pageshared' => $pageshared,
                    'usershared' => $usershared,
                    'cacheexpire' => $cacheexpire,
                ))) return;

        break;

        case 'access':

            // only admins can modify block access
            if (!$adminaccess)
                return xarTpl::module('privileges','user','errors',array('layout' => 'no_privileges'));

            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            $isvalid = $accessproperty->checkInput($blockinfo['name'] . '_display');
            $blockinfo['content']['display_access'] = $accessproperty->value;
            $isvalid = $accessproperty->checkInput($blockinfo['name'] . '_modify');
            $blockinfo['content']['modify_access'] = $accessproperty->value;
            $isvalid = $accessproperty->checkInput($blockinfo['name'] . '_delete');
            $blockinfo['content']['delete_access'] = $accessproperty->value;

        break;

        default:
            // custom tab supplied by block, looks for a method named update{$tab}
            if ($instance['allowaccess']) {
                $tabmethod = 'update'.$tab;
                if (method_exists($block, $tabmethod)) {
                    $blockinfo = $block->$tabmethod();
                    // @FIXME: the update method must return an array of blockinfo
                    // need to raise an exception here if it doesn't
                }
            }
            $tab = null;
        break;
    }

    // Pass to API - do generic updates.
    if (!xarMod::apiFunc('blocks', 'admin', 'update_instance', $blockinfo)) {return;}

    // Resequence blocks within groups.
    if (!xarMod::apiFunc('blocks', 'admin', 'resequence')) {return;}

    $return_url =  !empty($blockinfo['return_url']) ? $blockinfo['return_url'] :
        xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid, 'tab' => $tab));

    xarController::redirect($return_url);
    return true;

}
?>
