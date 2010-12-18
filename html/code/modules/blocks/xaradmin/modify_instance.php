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
 * modify a block instance
 * @TODO Need to sperate this out to API calls.
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
 */

function blocks_admin_modify_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'int:1:', $bid, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('tab', 'pre:trim:lower:str:1', $tab, 'config', XARVAR_NOT_REQUIRED)) return;
    
    // Security
    if (empty($bid)) return xarResponse::notFound();
    // @CHECKME: exception if the block is not found, does get do that?
    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));
    // user needs admin access to modify block instance (name, title, etc)
    $adminaccess = xarSecurityCheck('',0,'Block',$instance['type'] . ":" . $instance['name'] . ":" . "$instance[bid]",$instance['module'],'',0,800);

    // Load block file, checks file exists, class exists and method (func) exists
    if (!xarMod::apiFunc('blocks', 'admin', 'load',
        array('module' => $instance['module'], 'type' => $instance['type'], 'func' => 'modify'))) return;

    // cascading block files - order is method specific, admin specific, block specific
    $to_check = array();
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'BlockModify';   // from eg menu_modify.php
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'BlockAdmin';    // from eg menu_admin.php
    $to_check[] = ucfirst($instance['module']) . '_' . ucfirst($instance['type']) . 'Block';         // from eg menu.php
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
        throw new ClassNotFoundException($className);
    }

    // string of template data (from modify-{$type}.xt template)
    // or privilege error template, default is empty string
    $block_modify = '';
    // checkAccess for modify method
    // user needs modify access to modify the block type properties
    if (!$block->checkAccess('modify')) {
        // render privilege error if we're showing modify access failure
        if (isset($block->modify_access) && $block->modify_access['failure']) {
            // @TODO: render to an error/exception block?
            $block_modify = xarTplModule('privileges','user','errors',array('layout' => 'no_block_privileges'));
        }
        $instance['allowaccess'] = false;
    } else {
        $instance['allowaccess'] = true;
        // now we're safe to call the block modify method
        // catching any exceptions thrown by the method or template rendering
        try {
            // Note: blockinfo here is $content
            $blockinfo = $block->modify();
            // it's ok if the method returns empty, thats not an error
            // it just means the block has nothing to render
            if (is_array($blockinfo)) {
                // Set some additional details that the could be useful in the block content.
                // @TODO: move these to basicblock class ?
                // prefix these extra variables (_bl_) to indicate they are supplied by the core.
                $blockinfo['_bl_block_id'] = $block->bid;
                $blockinfo['_bl_block_name'] = $block->name;
                $blockinfo['_bl_block_type'] = $block->type;
                if (!empty($block->groupid)) {
                    // The block may not be rendered as part of a group.
                    $blockinfo['_bl_block_groupid'] = $block->groupid;
                    $blockinfo['_bl_block_group'] = $block->group;
                }
                // Legacy (deprecated)
                // @TODO: remove these once all block templates are using the _bl_ variables
                $blockinfo['blockid'] = $block->bid;
                $blockinfo['blockname'] = $block->name;
                $blockinfo['blocktypename'] = $block->type;
                if (isset($block->groupid)) {
                    // The block may not be rendered as part of a group.
                    $blockinfo['blockgid'] = $block->groupid;
                    $blockinfo['blockgroupname'] = $block->group;
                }
                // Render the extra settings if necessary.
                // Again we check for an exception, this time in the template rendering
                try {
                    $block_modify = xarTplBlock($instance['module'], 'modify-' . $instance['type'], $blockinfo);
                } catch (Exception $e) {
                    // @TODO: global flag to raise exceptions or not
                    if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                        $block_modify = '';
                    } else {
                        throw ($e);
                    }
                }
            } elseif (!empty($blockinfo) && is_string($blockinfo)) {
                // The output is already templated
                $block_modify = $blockinfo;
            }
        } catch (Exception $e) {
            // @TODO: global flag to raise exceptions or not
            if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                $block_modify = '';
            } else {
                throw ($e);
            }
        }
    }

    // build our form data :)
    $data = array();

    // only display data necessary for the current tab
    switch ($tab) {

        case 'config':

            $templates = (strpos($instance['template'], ';') !== false) ?
                explode(';',$instance['template'],3) : array($instance['template']);
            $instance['template_outer'] = (!empty($templates[0])) ? $templates[0] : ''; // outer template
            $instance['template_inner'] = (!empty($templates[1])) ? $templates[1] : ''; // inner template

            if ($adminaccess) {

                if (!empty($block->expire)) {
                    $now = time();
                    $soon = $block->expire - $now ;
                    $instance['expirein'] = $soon;
                    if ($now > $block->expire && $block->expire != 0) {
                        $instance['expire'] = 0;
                    } else {
                        $instance['expire'] = $block->expire;
                    }
                } else {
                   $instance['expire'] = 0;
                   $instance['expirein'] = 0;
                }

                // @CHECKME: for now, blockgroup blocks are allowed in other blockgroup blocks
                // @TODO: check consequences of allowing blockgroups inside blockgroups
                // if ($instance['module'] != 'blocks' && $instance['type'] != 'blockgroup') {
                    // handle blockgroups
                    $blockgroups = xarMod::apiFunc('blocks', 'user', 'getall',
                        array('type' => 'blockgroup'));
                    // In the modify form, we want to provide an array of checkboxes: one for each group.
                    // Also a field for the overriding template name for each group instance.
                    foreach ($blockgroups as $key => $blockgroup) {
                        $id = $blockgroup['bid'];
                        $blockgroups[$key]['id'] = $id;
                        if (isset($instance['groups'][$id])) {
                            $blockgroups[$key]['selected'] = true;
                            $blockgroups[$key]['template'] = $instance['groups'][$id]['group_inst_template'];
                        } else {
                            $blockgroups[$key]['selected'] = false;
                            $blockgroups[$key]['template'] = null;
                        }
                        $grouptpls = (strpos($blockgroups[$key]['template'], ';') !== false) ?
                            explode(';',$blockgroups[$key]['template'],3) : array($blockgroups[$key]['template']);
                        $blockgroups[$key]['template_outer'] = (!empty($grouptpls[0])) ? $grouptpls[0] : ''; // outer template
                        $blockgroups[$key]['template_inner'] = (!empty($grouptpls[1])) ? $grouptpls[1] : ''; // inner template
                    }
                    $data['block_groups'] = $blockgroups;
                    // Set 'group_method' to 'min' for a compact group list,
                    // only showing those groups that have been selected.
                    // Set to 'max' to show all possible groups that the
                    // block could belong to.
                    // FIXME: this should either be optional or removed, not sure which
                    $data['group_method'] = 'min';
                //}

                // populate block state options
                $data['state_options'] = array(
                    array('id' => xarBlock::BLOCK_STATE_INACTIVE, 'name' => xarML('Inactive')),
                    array('id' => xarBlock::BLOCK_STATE_HIDDEN, 'name' => xarML('Hidden')),
                    array('id' => xarBlock::BLOCK_STATE_VISIBLE, 'name' => xarML('Visible')),
                );

            }

            if (!empty($block_modify)) {
                // handle block hooks
                $item = array();
                $item['module'] = 'blocks';
                $item['itemtype'] = 3; // block instance
                $item['itemid'] = $bid;
                $hooks = array();
                // @TODO: distinct block hooks
                // $hooks = xarModCallHooks('block', 'modify', $bid, $item);
                $hooks = xarModCallHooks('item', 'modify', $bid, $item);
                $data['hooks'] = $hooks;
            }

        break;

        case 'caching':
            // @CHECKME: gotta be an admin to access caching options?
            if (!$adminaccess)
                return xarTplModule('privileges','user','errors',array('layout' => 'no_privileges'));

            // get cache settings
            $cached = xarMod::apiFunc('blocks', 'user', 'getcacheblock', array('bid' => $bid));
            if (!empty($cached)) {
                $instance['nocache'] = $cached['nocache'];
                $instance['pageshared'] = $cached['pageshared'];
                $instance['usershared'] = $cached['usershared'];
                $instance['cacheexpire'] = $cached['cacheexpire'];
            // no settings, try getting settings from current instance
            } else {
                // get block type init settings
                // we can't just call getInit on the current block instance,
                // since we want the initial settings for this block type
                $initresult = xarMod::apiFunc('blocks', 'user', 'read_type_init',
                    array('module' => $instance['module'], 'type' => $instance['type']));
                // over-rides (for first run)
                $instance['nocache'] = isset($blockinfo['nocache']) ? $blockinfo['nocache'] :
                    (isset($initresult['nocache']) ? $initresult['nocache'] : 0);
                $instance['pageshared'] = isset($blockinfo['pageshared']) ? $blockinfo['pageshared'] :
                    (isset($initresult['pageshared']) ? $initresult['pageshared'] : 0);
                $instance['usershared'] = isset($blockinfo['usershared']) ? $blockinfo['usershared'] :
                    (isset($initresult['usershared']) ? $initresult['usershared'] : 0);
                $instance['cacheexpire'] = isset($blockinfo['cacheexpire']) ? $blockinfo['cacheexpire'] :
                    (isset($initresult['cacheexpire']) ? $initresult['cacheexpire'] : 0);
            }
            // convert expire time to hh:mm:ss format for display
            if (!empty($instance['cacheexpire'])) {
                $instance['cacheexpire'] = xarModAPIFunc('blocks', 'user', 'convertseconds', array('direction' => 'from', 'starttime' => $instance['cacheexpire']));
            }
            $data['usershared_options'] = array(
                array('id' => 0, 'name' => xarML('No Sharing')),
                array('id' => 1, 'name' => xarML('Group Members')),
                array('id' => 2, 'name' => xarML('All Users')),
            );

        break;

        case 'access':
            // gotta be an admin to access block access settings
            if (!$adminaccess)
                return xarTplModule('privileges','user','errors',array('layout' => 'no_privileges'));

            $instance['display_access'] = $block->display_access;
            $instance['modify_access'] = $block->modify_access;
            $instance['delete_access'] = $block->delete_access;

        break;

        case 'help':
            // handle block help method
            if (method_exists($block, 'help')) {
                try {
                    $blockhelp = $block->help();
                    if (!empty($blockhelp)) {
                        // if the method returned an array of data attempt to render
                        // to template blocks/help-{blockType}.xt
                        if (is_array($blockhelp)) {
                            // Render the extra settings if necessary.
                            // Again we check for an exception, this time in the template rendering
                            try {
                                $block_modify = xarTplBlock($blockinfo['module'], 'help-' . $blockinfo['type'], $blockhelp);
                            } catch (Exception $e) {
                                // @TODO: global flag to raise exceptions or not
                                if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                                    $block_modify = '';
                                } else {
                                    throw ($e);
                                }
                            }
                        // Legacy: old help functions return a string
                        } elseif (is_string($blockhelp)) {
                            $block_modify = $blockhelp;
                        }
                    }
                } catch (Exception $e) {
                    // @TODO: global flag to raise exceptions or not
                    if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                        $block_modify = '';
                    } else {
                        throw ($e);
                    }
                }
            } else {
                $block_modify = '';
            }

        break;

        default:

        break;
    }
    // display the reported block version too
    $instance['xarversion'] = !empty($block->xarversion) ? $block->xarversion : xarML('Unknown');

    // variables available to all tabs
    $data['bid'] = $bid;
    $data['instance'] = $instance;
    $data['authid'] = xarSecGenAuthKey();
    $data['block_modify'] = $block_modify;

    // supply block tabs for config, caching and access forms
    // @TODO: allow blocks to add their own tabs to this array
    // if (!empty($blockinfo['blocktabs'])) { }
    $blocktabs = array();
    $blocktabs['config'] = array(
            'url' => xarServer::getCurrentURL(array('tab' => 'config')),
            'title' => xarML('Modify block configuration'),
            'label' => xarML('Config'),
            'active' => $tab == 'config',
        );
    // caching and access only available to admins
    if ($adminaccess) {
        $blocktabs['caching'] = array(
                'url' => xarServer::getCurrentURL(array('tab' => 'caching')),
                'title' => xarML('Modify block caching configuration'),
                'label' => xarML('Caching'),
                'active' => $tab == 'caching',
            );
        if ($adminaccess) {
            $blocktabs['access'] = array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'access')),
                    'title' => xarML('Modify block access configuration'),
                    'label' => xarML('Access'),
                    'active' => $tab == 'access',
                );
        }
    }
    // show a help tab if the block has a help method
    if (method_exists($block, 'help')) {
        $blocktabs['help'] = array(
            'url' => xarServer::getCurrentURL(array('tab' => 'help')),
            'title' => xarML('Help with block configuration'),
            'label' => xarML('Help'),
            'active' => $tab == 'help',
        );
    }

    $data['blocktabs'] = $blocktabs;
    $data['tab'] = $tab;

    // flag block administrators
    $data['adminaccess'] = $adminaccess;
    return $data;

}
?>