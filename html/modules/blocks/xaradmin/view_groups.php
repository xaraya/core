<?php

/**
 * view block groups
 */
function blocks_admin_view_groups()
{
    // Security Check
	if(!xarSecurityCheck('AdminBlock',0,'Instance')) return;
    $authid = xarSecGenAuthKey();
    // Load up database
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];

    $query = "SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      $block_groups_table
              ORDER BY  xar_name ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Load up groups array
    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);
        // Get details on current group
        $group = xarModAPIFunc('blocks', 
                               'admin', 
                               'groupgetinfo', array('blockGroupId' => $group['id']));
        $group['membercount'] = count($group['instances']);
        $group['javascript'] = "return confirmLink(this, '" . xarML('Delete group') ." : $group[name] ?')";
        $group['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_group', array('gid' => $group['id'], 'authid' => $authid));
        $block_groups[] = $group;

        $result->MoveNext();
    }

    return array('block_groups' => $block_groups);
}

?>