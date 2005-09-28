<?php
/**
 * File: $Id$
 *
 * Modify a block instance
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * modify a block instance
 * @TODO Need to sperate this out to API calls.
 */

function blocks_admin_modify_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}

    // Security Check
    if (!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}

    // Get the instance details.
    $instance = xarModAPIfunc('blocks', 'user', 'get', array('bid' => $bid));

    // Load block
    if (!xarModAPIFunc(
        'blocks', 'admin', 'load',
        array(
            'modName' => $instance['module'],
            'blockName' => $instance['type'],
            'blockFunc' => 'modify')
        )
    ) {return;}

    // Determine the name of the update function.
    // Execute the function if it exists.
    $usname = preg_replace('/ /', '_', $instance['module']);
    $modfunc = $usname . '_' . $instance['type'] . 'block_modify';

    if (function_exists($modfunc)) {
        $extra = $modfunc($instance);

        // if there was an error here, return...
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        if (is_array($extra)) {
            // Render the extra settings if necessary.
            $extra = xarTplBlock($instance['module'], 'modify-' . $instance['type'], $extra);
        }
    } else {
        $extra = '';
    }

    // Get the block info flags.
    $block_info = xarModAPIfunc(
        'blocks', 'user', 'read_type_info',
        array(
            'module' => $instance['module'],
            'type' => $instance['type']
        )
    );

    if (empty($block_info)) {
        // Function does not exist so throw error
        $msg = xarML(
            'Block info function for module "#(1)" and type "#(2)"',
            $instance['module'], $instance['type']
        );
        xarErrorSet(
            XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
            new SystemException(__FILE__.'('.__LINE__.'): '.$msg)
        );
        return NULL;
    }

    // Build refresh times array.
    // TODO: is this still used? Is it specific to certain types of block only?
    $refreshtimes = array(
        array('id' => 1800, 'name' => xarML('Half Hour')),
        array('id' => 3600, 'name' => xarML('Hour')),
        array('id' => 7200, 'name' => xarML('Two Hours')),
        array('id' => 14400, 'name' => xarML('Four Hours')),
        array('id' => 43200, 'name' => xarML('Twelve Hours')),
        array('id' => 86400, 'name' => xarML('Daily'))
    );

    // Fetch complete block group list.
    $block_groups = xarModAPIfunc('blocks', 'user', 'getallgroups');

    // In the modify form, we want to provide an array of checkboxes: one for each group.
    // Also a field for the overriding template name for each group instance.
    foreach ($block_groups as $key => $block_group) {
        $gid = $block_group['gid'];
        if (isset($instance['groups'][$gid])) {
            $block_groups[$key]['selected'] = true;
            $block_groups[$key]['template'] = $instance['groups'][$gid]['group_inst_template'];
        } else {
            $block_groups[$key]['selected'] = false;
            $block_groups[$key]['template'] = '';
        }
    }

    $args = array();
    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    $hooks = array();
    $hooks = xarModCallHooks('item', 'modify', $bid, $args);

    return array(
        'authid'         => xarSecGenAuthKey(),
        'bid'            => $bid,
        'block_groups'   => $block_groups,
        'instance'       => $instance,
        'extra_fields'   => $extra,
        'block_settings' => $block_info,
        'hooks'          => $hooks,
        'refresh_times'  => $refreshtimes,
        // Set 'group_method' to 'min' for a compact group list,
        // only showing those groups that have been selected.
        // Set to 'max' to show all possible groups that the
        // block could belong to.
        'group_method'   => 'min' // 'max'
    );
}

?>
