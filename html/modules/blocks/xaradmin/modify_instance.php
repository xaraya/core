<?php

/**
 * modify a block instance
 * @TODO Need to sperate this out to API calls.
 */
function blocks_admin_modify_instance()
{
    // Get parameters
    if (!xarVarFetch('bid','int:1:',$bid)) return;

    // Security Check
	if(!xarSecurityCheck('EditBlock',0,'Instance')) return;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table = $xartable['block_types'];

    // Fetch instance data
    $query = "SELECT inst.xar_id as id,
                     inst.xar_title as title,
                     inst.xar_template as template,
                     inst.xar_content as content,
                     inst.xar_refresh as refresh,
                     inst.xar_state as state,
                     group_inst.xar_group_id as group_id,
                     type.xar_module as module,
                     type.xar_type as type
              FROM   $block_instances_table as inst
              LEFT JOIN $block_group_instances_table as group_inst
              ON        group_inst.xar_instance_id = inst.xar_id
              LEFT JOIN $block_types_table as type
              ON        type.xar_id = inst.xar_type_id
              WHERE     inst.xar_id = $bid";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->RecordCount() != 1) {
        $msg = xarML('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return NULL;
    }

    // Fetch instance data
    $instance = $result->GetRowAssoc(false);

    // Block-specific
    // TODO: exception here
    xarBlock_Load($instance['module'], $instance['type']);

    $usname = preg_replace('/ /', '_', $instance['module']);
    $modfunc = $usname . '_' . $instance['type'] . 'block_modify';

    if (function_exists($modfunc)) {
        $extra = $modfunc($instance);
    } else {
        // TODO: adam_baum - add some error checking for non-existant func, methinks.
        $extra = '';
    }

    // check to see if block has form content
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

    // build refresh times array
    $refreshtimes = array(array('id' => 1800,
                                'name' => xarML('Half Hour')),
                          array('id' => 3600,
                                'name' => xarML('Hour')),
                          array('id' => 7200,
                                'name' => xarML('Two Hours')),
                          array('id' => 14400,
                                'name' => xarML('Four Hours')),
                          array('id' => 43200,
                                'name' => xarML('Twelve Hours')),
                          array('id' => 86400,
                                'name' => xarML('Daily')));

    // Position
    // Fetch block group list
    $block_groups_table = $xartable['block_groups'];
    $query = "SELECT xar_id as id, xar_name as name FROM $block_groups_table";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);

        $block_groups[] = $group;

        $result->MoveNext();
    }

    return array('authid'        => xarSecGenAuthKey(),
                 'bid'          => $bid,
                 'block_groups' => $block_groups,
                 'instance'     => $instance,
                 'extra_fields' => $extra,
                 'block_settings'=> $block_edit,
                 'refresh_times' => $refreshtimes);
}

?>
