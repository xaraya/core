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
    if(!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}

    // TODO: move all database stuff to the API.
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table = $xartable['block_types'];

    // Get the instance details.
    $instance = xarModAPIfunc('blocks', 'user', 'get', $bid);

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
        if (is_array($extra)) {
            // Render the extra settings if necessary.
            $extra = xarTplBlock($instance['module'], 'admin-' . $instance['type'], $extra);
        }
    } else {
        $extra = '';
    }

    // Check to see if block has form content.
    $infofunc = $usname.'_'.$instance['type'] . 'block_info';
    if (function_exists($infofunc)) {
        $block_edit = $infofunc();
    } else {
        // Function does not exist so throw error
        $msg = xarML('MODULE_FUNCTION_NOT_EXIST #(1)', $infofunc);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
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
    // TODO: move to API.
    $block_groups_table = $xartable['block_groups'];
    $query = 'SELECT xar_id as id, xar_name as name FROM ' . $block_groups_table;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);
        $block_groups[$group['id']] = $group;
        $result->MoveNext();
    }
    
    // In the modify form, we want to provide an array of checkboxes: one for each group.
    // Also a field for the overriding template name for each group instance.
    foreach ($block_groups as $key => $block_group) {
        if (isset($instance['groups'][$key])) {
            $block_groups[$key]['selected'] = true;
            $block_groups[$key]['template'] = $instance['groups'][$key]['group_inst_template'];
        } else {
            $block_groups[$key]['selected'] = false;
            $block_groups[$key]['template'] = '';
        }
    }

    $hooks = xarModCallHooks('item', 'modify', $bid, '');
    //error_log("hooked to blocks = " . serialize($hooks));
    if (empty($hooks)) {
        $hooks = '';
    } elseif (is_array($hooks)) {
        $hooks = join('',$hooks);
    } else {
        $hooks = $hooks;
    }

    return array('authid'         => xarSecGenAuthKey(),
                 'bid'            => $bid,
                 'block_groups'   => $block_groups,
                 'instance'       => $instance,
                 'extra_fields'   => $extra,
                 'block_settings' => $block_edit,
                 'hooks'          => $hooks,
                 'refresh_times'  => $refreshtimes);
}

?>
