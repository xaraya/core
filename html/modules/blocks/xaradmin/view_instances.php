<?php

/**
 * view block instances
 */
function blocks_admin_view_instances()
{
// Security Check
	if(!xarSecurityCheck('EditBlock',0,'Instance')) return;
    $authid = xarSecGenAuthKey();
    // Load up database
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_instances_table = $xartable['block_instances'];
    $block_groups_table = $xartable['block_groups'];
    $block_types_table = $xartable['block_types'];

    $query = "SELECT    inst.xar_id as id,
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        bgroups.xar_name as group_name,
                        bgroups.xar_id as group_id,
                        group_inst.xar_position as position,
                        inst.xar_template as template,
                        bgroups.xar_template as group_template
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_groups_table as bgroups
              ON        bgroups.xar_id = group_inst.xar_group_id
              LEFT JOIN $block_instances_table as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $block_types_table as btypes
              ON        btypes.xar_id = inst.xar_type_id
              ORDER BY  group_inst.xar_group_id ASC,
                        group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Load up blocks array
    $blocks = array();
    while(!$result->EOF) {
        $block = $result->GetRowAssoc(false);

        switch ($block['state']) {
            case 0:
                $block['state_desc'] = 'Hidden';
                break;
            case 1:
                $block['state_desc'] = 'Minimized';
                break;
            case 2:
                $block['state_desc'] = 'Maximized';
                break;
        }
        $block['javascript'] = "return xar_base_confirmLink(this, '" . xarML('Delete instance') . " $block[title] ?')";
        $block['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_instance', array('bid' => $block['id'], 'authid' => $authid));
        $blocks[] = $block;

        $result->MoveNext();
    }

    $data['selstyle']                               = xarModGetUserVar('blocks', 'selstyle');
    if (empty($data['selstyle'])){
        $data['selstyle'] = 'plain';
    }
    
    // select vars for drop-down menus
    $data['style']['plain']                         = xarML('Plain');
    $data['style']['compact']                       = xarML('Compact');

    $data['blocks'] = $blocks;

    // Include 'confirmlink' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'confirmlink.js')
    );

    return $data;
}

?>
