<?php

/**
 * modify block group
 */
function blocks_admin_modify_group()
{
    $gid = xarVarCleanFromInput('gid');

// Security Check
	if(!xarSecurityCheck('EditBlock',0,'Group')) return;

    // Load up database
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_instances_table = $xartable['block_instances'];
    $block_groups_table = $xartable['block_groups'];
    $block_types_table = $xartable['block_types'];

    $group = xarBlockGroupGetInfo($gid);

    //Get Module Info For up,down images
    $modinfo = xarModGetInfo(xarModGetIDFromName(xarModGetName()));

    $up_arrow_src = 'modules/'. xarVarPrepForDisplay($modinfo['directory']) .'/xarimages/up.gif';
    $down_arrow_src = 'modules/'. xarVarPrepForDisplay($modinfo['directory']) .'/xarimages/down.gif';

    return array('group' => $group,
                 'instance_count' => count($group['instances']),
                 'up_arrow_src' => $up_arrow_src,
                 'down_arrow_src' => $down_arrow_src,
                 'authid' => xarSecGenAuthKey());

    /*
    $options = array();
        if ($active) {
            $state = _ACTIVE;
            $options[] = $output->URL(xarModURL('blocks',
                                               'admin',
                                               'deactivate',
                                               array('bid' => $bid,
                                                     'authid' => $authid)),
                                               _DEACTIVATE);
        } else {
            $state = _INACTIVE;
            $options[] = $output->URL(xarModURL('blocks',
                                               'admin',
                                               'activate',
                                               array('bid' => $bid,
                                                     'authid' => $authid)),
                                               _ACTIVATE);
        }

*/
}

?>