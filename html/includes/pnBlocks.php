<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file:  Paul Rosania
// Purpose of file: Display Blocks
// ----------------------------------------------------------------------

/*
 * FIXME: <marco> Paul do you wanna move pnBlockTypeExists, Register and Unregister out of this file?
 * And why are you using $blockType instead of $blockName, when I said you to change I meant use $blockName everywhere, in the end it's the block name, not the block type, don't you think?
 */

/**
 * Get block information
 *
 * @access public
 * @param blockId the block id
 * @returns array
 * @return resarray array of block information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function pnBlockGetInfo($blockId)
{
    if (empty($blockId)) {
        $msg = pnML('Empty bid.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return NULL;
    }
    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_instances_table = $pntable['block_instances'];
    $block_types_table = $pntable['block_types'];
    $block_groups_table = $pntable['block_groups'];
    $block_group_instances_table = $pntable['block_group_instances'];

    $query = "SELECT    inst.pn_id as id,
                        inst.pn_title as title,
                        inst.pn_template as template,
                        inst.pn_content as content,
                        inst.pn_refresh as refresh,
                        inst.pn_state as state,
                        inst.pn_last_update as last_update,
                        group_inst.pn_group_id as group_id,
                        type.pn_module as module,
                        type.pn_type as type,
                        groups.pn_name as group_name
              FROM      $block_instances_table as inst
              LEFT JOIN $block_group_instances_table as group_inst
              ON        group_inst.pn_instance_id = inst.pn_id
              LEFT JOIN $block_types_table as type
              ON        type.pn_id = inst.pn_type_id
              LEFT JOIN $block_groups_table as groups
              ON        groups.pn_id = group_inst.pn_group_id
              WHERE     inst.pn_id = $blockId";

    $result = $dbconn->Execute($query);
    echo $dbconn->ErrorMsg();
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }
    if ($result->EOF) {
        $result->Close();
        $msg = pnML('Block identified by bid #(1) doesn\'t exist.', $blockId);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                       new SystemException($msg));
	    return NULL;
    }

    $block_info = $result->GetRowAssoc(false);
    $block_info['mid'] = $block_info['module'];
    $block_info['bkey'] = $block_info['id'];

    $result->Close();

    return $block_info;
}

/**
 * Get block group information
 *
 * @access public
 * @param blockGroupID the block group id
 * @returns array
 * @return resarray array of block information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function pnBlockGroupGetInfo($blockGroupId)
{
    if (empty($blockGroupId)) {
        $msg = pnML('Empty group ID (gid).');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return NULL;
    }

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_instances_table = $pntable['block_instances'];
    $block_types_table = $pntable['block_types'];
    $block_groups_table = $pntable['block_groups'];
    $block_group_instances_table = $pntable['block_group_instances'];

    $query = "SELECT    pn_id as id,
                        pn_name as name,
                        pn_template as template
              FROM      $block_groups_table
              WHERE     pn_id = $blockGroupId";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = pnML("Group ID $blockGroupId not found.", $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return NULL;
    }

    $group = $result->GetRowAssoc(false);

    $result->Close();

    // Query for instances in this group
    $query = "SELECT    inst.pn_id as id,
                        types.pn_type as type,
                        types.pn_module as module,
                        inst.pn_title as title,
                        group_inst.pn_position as position
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_groups_table as groups
              ON        group_inst.pn_group_id = groups.pn_id
              LEFT JOIN $block_instances_table as inst
              ON        inst.pn_id = group_inst.pn_instance_id
              LEFT JOIN $block_types_table as types
              ON        types.pn_id = inst.pn_type_id
              WHERE     groups.pn_id = '$blockGroupId'
              ORDER BY  group_inst.pn_position ASC";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }

    // Load up list of group's instances
    $instances = array();
    while(!$result->EOF) {
        $instances[] = $result->GetRowAssoc(false);
        $result->MoveNext();
    }

    $result->Close();

    $group['instances'] = $instances;

    return $group;
}

/**
 * Check for existance of a block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true if exists, false if not found
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnBlockTypeExists($modName, $blockType)
{
    if (empty($modName) || empty($blockType)) {
        $msg = pnML('Empty module name (#(0)) or type (#(1))', $modName, $blockType);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_types_table = $pntable['block_types'];

    $query = "SELECT    pn_id as id
              FROM      $block_types_table
              WHERE     pn_module = '$modName'
              AND       pn_type = '$blockType'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    // Got exactly 1 result, it exists
    if ($result->PO_RecordCount() == 1) {
        list ($id) = $result->fields;
        return $id;
    }

    // Freak if we don't get zero or one one result
    if ($result->PO_RecordCount() > 1) {
        $msg = pnML('Multiple instances of block type #(0) found in module #(1)!', $blockType, $modName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    return false;
}

/**
 * Register block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnBlockTypeRegister($modName, $blockType)
{
    if (empty($modName) || empty($blockType)) {
        $msg = pnML('Empty module name (#(1)) or type (#(2))', $modName, $blockType);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    if (pnBlockTypeExists($modName, $blockType)) {
        $msg = pnML('Block type #(1) already exists in the #(2) module', $blockType, $modName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_types_table = $pntable['block_types'];

    $seq_id = $dbconn->GenId($block_types_table);
    $query = "INSERT INTO $block_types_table (pn_id, pn_module, pn_type) VALUES ('$seq_id', '$modName', '$blockType');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    return true;
}

/**
 * Unregister block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnBlockTypeUnregister($modName, $blockType)
{
    if (!pnBlockTypeExists($modName, $blockType)) {
        return true;
    }

    list ($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_types_table = $pntable['block_types'];

    $query = "DELETE FROM $block_types_table WHERE pn_module = '$modName' AND pn_type = '$blockType';";
    $dbconn->Execute();

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    return true;
}

// PROTECTED FUNCTIONS

/**
 * Load a block
 *
 * @access private
 * @param modName the module name
 * @param blockType the name of the block
 * @returns bool
 * @return true|false
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnBlock_load($modName, $blockName)
{
    /*if (empty($modName) || empty($blockName)) {
        $msg = pnML('Empty modname (#(1)) or block (#(2)).', $modName, $blockName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }*/
    static $loaded = array();

    if (isset($loaded["$modName$blockName"])) {
        return true;
    }
    $modBaseInfo = pnMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back exception
    }
    $moddir = 'modules/' . $modBaseInfo['osdirectory'] . '/pnblocks';

    // Load the block
    $incfile = $blockName . ".php";
    $filepath = $moddir . '/' . pnVarPrepForOS($incfile);

    if (!file_exists($filepath)) {
        $msg = pnML('Block file #(1) doesn\'t exist.', $filepath);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
    include $filepath;
    $loaded["$modName$blockName"] = 1;

    // Load the block language files
    pnMLS_loadBlockTranslations($blockName, $modBaseInfo['osdirectory']);

    // Initialise block (security schema) if required.
    $initfunc = "{$modName}_{$blockName}block_init";
    if (function_exists($initfunc)) {
        $initfunc();
    }
    return true;
}

/**
 * Load all blocks
 *
 * @access private
 * @returns array
 * @return blocks_modules the aray of blocks on success, false otherwise
 * @raise DATABASE_ERROR
 */
function pnBlock_loadAll()
{
    // Load blocks
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $modNametable = $pntable['modules'];

    $query = "SELECT pn_name,
                   pn_directory,
                   pn_regid
            FROM $modNametable";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    while (!$result->EOF) {
        list($name, $directory, $mid) = $result->fields;
        $result->MoveNext();
        $blockDir = 'modules/' . pnVarPrepForOS($directory) . '/pnblocks';
        if (!@is_dir($blockDir)) {
            continue;
        }
        $dib = opendir($blockDir);
        while($f = readdir($dib)) {
            if (preg_match('/\.php$/', $f)) {
                $blockName = preg_replace('/\.php$/', '', $f);
                if (!pnBlock_load($name, $blockName)) {
                    // Block load failed
                    return false;
                }
                // Get info on the block
                $usname = preg_replace('/ /', '_', $name);
                $infofunc = $usname . '_' . $blockName . 'block_info';
                if (function_exists($infofunc)) {
                    $blocks_modules["$name$blockName"] = $infofunc();
                    $blocks_modules["$name$blockName"]['bkey'] = $blockName;
                    $blocks_modules["$name$blockName"]['mid'] = $mid;
                }
            }
        }
    }
    $result->Close();
    // Return information gathered
    return $blocks_modules;
}

/**
 * Renders a block
 *
 * @access private
 * @param blockInfo block information parameters
 * @return output the block to show
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnBlock_render($blockInfo)
{
    $modName = $blockInfo['module'];
    $blockType = $blockInfo['type'];

    if (empty($modName) || empty($blockType)) {
        $msg = pnML('Empty modname (#(1)) or block type (#(2)).', $modName, $blockType);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $res = pnBlock_load($modName, $blockType);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    $displayFuncName = "{$modName}_{$blockType}block_display";

    // fetch complete blockinfo array
    if (function_exists($displayFuncName)) {
        $blockInfo = $displayFuncName($blockInfo);

        if (empty($blockInfo)) {
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back exception
            }
            return '';
        }
        if (!is_array($blockInfo)) {
            $msg = pnML('The block function #(1) didn\'t produce a valid block info type result.', $displayFuncName);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }
        // Handle the new block templating style
        if (is_array($blockInfo['content'])) {
            // Here $blockInfo['content'] is $tplData
            $templateName = NULL;
            if (isset($blockInfo['content']['_bl_template'])) {
                $templateName = $blockInfo['content']['_bl_template'];
            }
            $blockInfo['content'] = pnTplBlock($modName, $blockType, $blockInfo['content'], $templateName);
        }
    } else {
		$msg = pnML('Module block function #(1) doesn\'t exist.', $displayFuncName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
	}

    // Handle block state
    $res = pnModAPILoad('blocks');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    $res = pnModAPIFunc('blocks', 'user', 'getState', $blockInfo);
    if (!$res) {
        if (pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back
        $blockInfo['content'] = '';
    }

    // Determine which block box template to use
    // FIXME: <marco> Remove this!
    if (!empty($blockInfo['template'])) {
        $msg = 'You must use _bl_template instead of template.';
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $templateName = NULL;
    if (isset($blockInfo['_bl_template'])) {
        $templateName = $blockInfo['_bl_template'];
    }

    return pnTpl_renderBlockBox($blockInfo, $templateName);
}

/**
 * Renders a block group
 *
 * @access private
 * @param groupName the name of the block group
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function pnBlock_renderGroup($groupName)
{
    /*if (!isset($groupName)){
        $msg = pnML('Empty group_name (#(1)).', $groupName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }*/

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $block_group_instances_table = $pntable['block_group_instances'];
    $block_instances_table = $pntable['block_instances'];
    $block_groups_table = $pntable['block_groups'];
    $block_types_table = $pntable['block_types'];

    // FIXME: Should use UNION instead of LEFT JOIN(?) - Paul
    $query = "SELECT    inst.pn_id as bid,
                        types.pn_type as type,
                        types.pn_module as module,
                        inst.pn_title as title,
                        inst.pn_content as content,
                        inst.pn_last_update as last_update,
                        inst.pn_state as state,
                        group_inst.pn_position as position,
                        groups.pn_template as _bl_template
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_groups_table as groups
              ON        group_inst.pn_group_id = groups.pn_id
              LEFT JOIN $block_instances_table as inst
              ON        inst.pn_id = group_inst.pn_instance_id
              LEFT JOIN $block_types_table as types
              ON        types.pn_id = inst.pn_type_id
              WHERE     groups.pn_name = '$groupName'
              AND       inst.pn_state > 0
              ORDER BY  group_inst.pn_position ASC";

    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }

    $output = '';
    while(!$result->EOF) {
        $blockInfo = $result->GetRowAssoc(false);
        $blockInfo['last_update'] = $result->UnixTimeStamp($blockInfo['last_update']);

        $output .= pnBlock_render($blockInfo);
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            return NULL; // throw back exception
        }

        $result->MoveNext();
    }

    $result->Close();

    return $output;
}

?>
