<?php
/**
 * View block types
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */

/**
 * view block types
 * @author Jim McDonald, Paul Rosania 
 */
function blocks_admin_view_types()
{
    // Security Check
    if (!xarSecurityCheck('EditBlock')) {return;}

    // Parameter to indicate a block type for which to get further details.
    if (!xarVarFetch('tid', 'id', $tid, 0, XARVAR_NOT_REQUIRED)) {return;}

    $params = array();
    $info = array();
    $detail = array();
    if (!empty($tid)) {
        // Get details for a specific block type.
        $detail = xarModAPIfunc(
            'blocks', 'user', 'getblocktype', array('tid' => $tid)
        );
        if (!empty($detail)) {
            // The block type exists.

            // Get info data.
            $info = xarModAPIfunc(
                'blocks', 'user', 'read_type_info',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );

            // Get initialisation data.
            $init = xarModAPIfunc(
                'blocks', 'user', 'read_type_init',
                array(
                    'module' => $detail['module'],
                    'type' => $detail['type']
                )
            );

            if (is_array($init)) {
                // Parse the initialisation data to extract further details.
                foreach($init as $key => $value) {
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
        }
    }

    $block_types = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes', array('order' => 'module,type')
    );

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
        'detail' => $detail
    );
}

?>