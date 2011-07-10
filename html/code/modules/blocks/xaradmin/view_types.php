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
 * view block types
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array data for the template display
 */
function blocks_admin_view_types()
{
    // Security
    if (!xarSecurityCheck('EditBlocks')) {return;}

    // Parameter to indicate a block type for which to get further details.
    if (!xarVarFetch('tid', 'id', $tid, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirm', 'int', $confirm, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:', $tab, NULL, XARVAR_NOT_REQUIRED)) return;

    $params = array();
    $info = array();
    $detail = array();
    if (!empty($tid)) {
        // Get details for a specific block type.
        $detail = xarMod::apiFunc('blocks', 'user', 'getblocktype', array('tid' => $tid));
        if (!empty($detail)) {
            // The block type exists.
            // Get info data.
            $info = xarMod::apiFunc(
                'blocks', 'user', 'read_type_info',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );
            if(!isset($info['new_access'])) $info['new_access'] = array();

            // Get initialisation data.
            $init = xarMod::apiFunc(
                'blocks', 'user', 'read_type_init',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );

            if (is_array($init)) {
                // Parse the initialisation data to extract further details.
                foreach($init as $key => $value) {
                    // not allowed to change xarversion
                    if ($key == 'xarversion') continue;
                    $valuetype = gettype($value);
                    $params[$key]['name'] = $key;

                    if ($valuetype == 'string') {
                        $value = "'" . $value . "'";
                    }

                    if ($valuetype == 'boolean') {
                        if ($value) {
                            $params[$key]['value'] = 'true';
                        } else {
                            $params[$key]['value'] = 'false';
                        }
                    } else {
                        $params[$key]['value'] = $value;
                    }

                    $params[$key]['type'] = $valuetype;
                    if ($valuetype == 'boolean' || $valuetype == 'integer' || $valuetype == 'float' || $valuetype == 'string' || $valuetype == 'NULL') {
                        $params[$key]['overrideable'] = true;
                    } else {
                        $params[$key]['overrideable'] = false;
                    }
                }
            }
            $blocktabs = array();
            $blocktabs['detail'] = array(
                'url' => xarServer::getCurrentURL(array('tab' => null)),
                'title' => xarML('View details about this block type'),
                'label' => xarML('Details'),
                'active' => empty($tab) || $tab == 'detail',
            );
            // cascading block files - order is method specific, admin specific, block specific
            $to_check = array();
            $to_check[] = ucfirst($detail['module']) . '_' . ucfirst($detail['type']) . 'BlockAdmin';    // from eg menu_admin.php
            $to_check[] = ucfirst($detail['module']) . '_' . ucfirst($detail['type']) . 'Block';         // from eg menu.php
            foreach ($to_check as $className) {
                // @FIXME: class name should be unique
                if (class_exists($className)) {
                    // instantiate the block instance using the first class we find
                    $block = new $className();
                    break;
                }
            }
            // make sure we instantiated a block,
            if (empty($block)) {
                // return classname not found (this is always class [$type]Block)
                throw new ClassNotFoundException($className);
            }
            if (method_exists($block, 'help')) {
                $blocktabs['help'] = array(
                    'url' => xarServer::getCurrentURL(array('tab' => 'help')),
                    'title' => xarML('View help information about this block type'),
                    'label' => xarML('Help'),
                    'active' => $tab == 'help',
                );
                if ($tab == 'help') {
                    try {
                        $blockhelp = $block->help();
                        if (!empty($blockhelp)) {
                            // if the method returned an array of data attempt to render
                            // to template blocks/help-{blockType}.xt
                            if (is_array($blockhelp)) {
                                // Render the extra settings if necessary.
                                // Again we check for an exception, this time in the template rendering
                                try {
                                    $block_help = xarTplBlock($detail['module'], 'help-' . $detail['type'], $blockhelp);
                                } catch (Exception $e) {
                                    // @TODO: global flag to raise exceptions or not
                                    if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                                        $block_help = '';
                                    } else {
                                        throw ($e);
                                        //$block_help = '';
                                    }
                                }
                            // Legacy: old help functions return a string
                            } elseif (is_string($blockhelp)) {
                                $block_help = $blockhelp;
                            }
                        }
                    } catch (Exception $e) {
                        // @TODO: global flag to raise exceptions or not
                        if ((bool)xarModVars::get('blocks', 'noexceptions')) {
                            $block_help = '';
                        } else {
                            throw ($e);
                            //$block_help = '';
                        }
                    }
                }
            }

        }
        if ($confirm) {
            sys::import('modules.dynamicdata.class.properties.master');
            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            $isvalid = $accessproperty->checkInput($detail['type'] . '_new');
            $info['new_access'] = $accessproperty->value;
            xarMod::apiFunc('blocks', 'admin', 'update_type_info', array('tid' => $tid, 'info' => $info));
        }
    }

    $block_types = xarMod::apiFunc('blocks', 'user', 'getallblocktypes', array('order' => 'modid,type'));

    // Add in some extra details.
    foreach($block_types as $index => $block_type) {
        $block_types[$index]['modurl'] = xarModURL($block_type['module'], 'admin');
        $block_types[$index]['refreshurl'] = xarModURL(
            'blocks', 'admin', 'update_type_info',
            array('modulename'=>$block_type['module'], 'blocktype'=>$block_type['type'])
        );
        $block_types[$index]['detailurl'] = xarModURL(
            'blocks', 'admin', 'view_types',
            array('tid'=>$block_type['tid'])
        );
        $block_types[$index]['info'] = $block_type['info'];
    }

    return array(
        'block_types' => $block_types,
        'tid' => $tid,
        'params' => $params,
        'info' => $info,
        'detail' => $detail,
        'blocktabs' => !empty($blocktabs) ? $blocktabs : array(),
        'block_help' => !empty($block_help) ? $block_help : '',
        'tab' => $tab,
    );
}

?>
