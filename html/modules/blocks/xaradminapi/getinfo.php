<?php
/**
 * Get block information
 *
 * @access public
 * @param integer blockId  the block id
 * @return array block information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_adminapi_getinfo($args)
{
    extract($args);

    if ($blockId < 1) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'blockId');
        return;
    }

    if (xarVarIsCached('Block.Infos', $blockId)) {
        return xarVarGetCached('Block.Infos', $blockId);
    }

    list ($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockInstancesTable      = $tables['block_instances'];
    $blockTypesTable          = $tables['block_types'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockGroupInstancesTable = $tables['block_group_instances'];

    $query = "SELECT    inst.xar_id as id,
                        inst.xar_title as title,
                        inst.xar_template as template,
                        inst.xar_content as content,
                        inst.xar_refresh as refresh,
                        inst.xar_state as state,
                        inst.xar_last_update as last_update,
                        group_inst.xar_group_id as group_id,
                        btypes.xar_module as module,
                        btypes.xar_type as type,
                        bgroups.xar_name as group_name
              FROM      $blockInstancesTable as inst
              LEFT JOIN $blockGroupInstancesTable as group_inst
              ON        group_inst.xar_instance_id = inst.xar_id
              LEFT JOIN $blockTypesTable as btypes
              ON        btypes.xar_id = inst.xar_type_id
              LEFT JOIN $blockGroupsTable as bgroups
              ON        bgroups.xar_id = group_inst.xar_group_id
              WHERE     inst.xar_id = $blockId";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        $msg = xarML('Block identified by bid #(1) doesn\'t exist.', $blockId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                       new SystemException($msg));
        return NULL;
    }

    $blockInfo = $result->GetRowAssoc(false);

    $blockInfo['mid']  = $blockInfo['module'];
    $blockInfo['bkey'] = $blockInfo['id'];

    $result->Close();

    xarVarSetCached('Block.Infos', $blockId, $blockInfo);

    return $blockInfo;
}

?>