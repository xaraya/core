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
 * FIXME: <marco> Paul do you wanna move xarBlockTypeExists, Register and Unregister out of this file?
 * And why are you using $blockType instead of $blockName, when I said you to change I meant use $blockName everywhere, in the end it's the block name, not the block type, don't you think?
 */

function xarBlock_init($args)
{
    // Blocks Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables = array('blocks' => $systemPrefix . '_blocks',
                    'block_instances' => $systemPrefix . '_block_instances',
                    'block_groups' => $systemPrefix . '_block_groups',
                    'block_group_instances' => $systemPrefix . '_block_group_instances',
                    'block_types' => $systemPrefix . '_block_types');

    xarDB_importTables($tables);
}

/**
 * Get block information
 *
 * @access public
 * @param blockId the block id
 * @returns array
 * @return resarray array of block information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarBlockGetInfo($blockId)
{
    if (empty($blockId)) {
        $msg = xarML('Empty bid.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return NULL;
    }
    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_instances_table = $xartable['block_instances'];
    $block_types_table = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "SELECT    inst.xar_id as id,
                        inst.xar_title as title,
                        inst.xar_template as template,
                        inst.xar_content as content,
                        inst.xar_refresh as refresh,
                        inst.xar_state as state,
                        inst.xar_last_update as last_update,
                        group_inst.xar_group_id as group_id,
                        type.xar_module as module,
                        type.xar_type as type,
                        groups.xar_name as group_name
              FROM      $block_instances_table as inst
              LEFT JOIN $block_group_instances_table as group_inst
              ON        group_inst.xar_instance_id = inst.xar_id
              LEFT JOIN $block_types_table as type
              ON        type.xar_id = inst.xar_type_id
              LEFT JOIN $block_groups_table as groups
              ON        groups.xar_id = group_inst.xar_group_id
              WHERE     inst.xar_id = $blockId";

    $result = $dbconn->Execute($query);
    echo $dbconn->ErrorMsg();
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }
    if ($result->EOF) {
        $result->Close();
        $msg = xarML('Block identified by bid #(1) doesn\'t exist.', $blockId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
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
function xarBlockGroupGetInfo($blockGroupId)
{
    if (empty($blockGroupId)) {
        $msg = xarML('Empty group ID (gid).');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return NULL;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_instances_table = $xartable['block_instances'];
    $block_types_table = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      $block_groups_table
              WHERE     xar_id = $blockGroupId";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group ID $blockGroupId not found.", $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return NULL;
    }

    $group = $result->GetRowAssoc(false);

    $result->Close();

    // Query for instances in this group
    $query = "SELECT    inst.xar_id as id,
                        types.xar_type as type,
                        types.xar_module as module,
                        inst.xar_title as title,
                        group_inst.xar_position as position
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_groups_table as groups
              ON        group_inst.xar_group_id = groups.xar_id
              LEFT JOIN $block_instances_table as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $block_types_table as types
              ON        types.xar_id = inst.xar_type_id
              WHERE     groups.xar_id = '$blockGroupId'
              ORDER BY  group_inst.xar_position ASC";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
function xarBlockTypeExists($modName, $blockType)
{
    if (empty($modName) || empty($blockType)) {
        $msg = xarML('Empty module name (#(0)) or type (#(1))', $modName, $blockType);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $query = "SELECT    xar_id as id
              FROM      $block_types_table
              WHERE     xar_module = '$modName'
              AND       xar_type = '$blockType'";

    $result = $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
        $msg = xarML('Multiple instances of block type #(0) found in module #(1)!', $blockType, $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
function xarBlockTypeRegister($modName, $blockType)
{
    if (empty($modName) || empty($blockType)) {
        $msg = xarML('Empty module name (#(1)) or type (#(2))', $modName, $blockType);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    if (xarBlockTypeExists($modName, $blockType)) {
        $msg = xarML('Block type #(1) already exists in the #(2) module', $blockType, $modName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $seq_id = $dbconn->GenId($block_types_table);
    $query = "INSERT INTO $block_types_table (xar_id, xar_module, xar_type) VALUES ('$seq_id', '$modName', '$blockType');";
    $dbconn->Execute($query);

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
function xarBlockTypeUnregister($modName, $blockType)
{
    if (!xarBlockTypeExists($modName, $blockType)) {
        return true;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $query = "DELETE FROM $block_types_table WHERE xar_module = '$modName' AND xar_type = '$blockType';";
    $dbconn->Execute();

    // Check for db errors
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $dbconn->ErrorMsg(), $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
function xarBlock_load($modName, $blockName)
{
    /*if (empty($modName) || empty($blockName)) {
        $msg = xarML('Empty modname (#(1)) or block (#(2)).', $modName, $blockName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
	    return;
    }*/
    static $loaded = array();

    if (isset($loaded["$modName$blockName"])) {
        return true;
    }
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) {
        return; // throw back exception
    }
    $moddir = 'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // Load the block
    $incfile = $blockName . ".php";
    $filepath = $moddir . '/' . xarVarPrepForOS($incfile);

    if (!file_exists($filepath)) {
        $msg = xarML('Block file #(1) doesn\'t exist.', $filepath);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }
    include $filepath;
    $loaded["$modName$blockName"] = 1;

    // Load the block language files
    if (xarMLS_loadTranslations('module', $modName, 'modules/'.$modBaseInfo['osdirectory'], 'block', $blockName) === NULL) return;

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
function xarBlock_loadAll()
{
    // Load blocks
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $modNametable = $xartable['modules'];

    $query = "SELECT xar_name,
                   xar_directory,
                   xar_regid
            FROM $modNametable";
    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }
    while (!$result->EOF) {
        list($name, $directory, $mid) = $result->fields;
        $result->MoveNext();
        $blockDir = 'modules/' . xarVarPrepForOS($directory) . '/xarblocks';
        if (!@is_dir($blockDir)) {
            continue;
        }
        $dib = opendir($blockDir);
        while($f = readdir($dib)) {
            if (preg_match('/\.php$/', $f)) {
                $blockName = preg_replace('/\.php$/', '', $f);
                if (!xarBlock_load($name, $blockName)) {
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
function xarBlock_render($blockInfo)
{
    $modName = $blockInfo['module'];
    $blockType = $blockInfo['type'];

    if (empty($modName) || empty($blockType)) {
        $msg = xarML('Empty modname (#(1)) or block type (#(2)).', $modName, $blockType);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $res = xarBlock_load($modName, $blockType);
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    $displayFuncName = "{$modName}_{$blockType}block_display";

    // fetch complete blockinfo array
    if (function_exists($displayFuncName)) {
        $blockInfo = $displayFuncName($blockInfo);

        if (empty($blockInfo)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return; // throw back exception
            }
            return '';
        }
        if (!is_array($blockInfo)) {
            $msg = xarML('The block function #(1) didn\'t produce a valid block info type result.', $displayFuncName);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
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
            $blockInfo['content'] = xarTplBlock($modName, $blockType, $blockInfo['content'], $templateName);
        }
    } else {
		$msg = xarML('Module block function #(1) doesn\'t exist.', $displayFuncName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST',
                       new SystemException($msg));
        return;
	}

    // Handle block state
    $res = xarModAPILoad('blocks');
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

    $res = xarModAPIFunc('blocks', 'user', 'getState', $blockInfo);
    if (!$res) {
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back
        $blockInfo['content'] = '';
    }

    // Determine which block box template to use
    // FIXME: <marco> Remove this!
    if (!empty($blockInfo['template'])) {
        $msg = 'You must use _bl_template instead of template.';
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $templateName = NULL;
    if (isset($blockInfo['_bl_template'])) {
        $templateName = $blockInfo['_bl_template'];
    }

    return xarTpl_renderBlockBox($blockInfo, $templateName);
}

/**
 * Renders a block group
 *
 * @access private
 * @param groupName the name of the block group
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function xarBlock_renderGroup($groupName)
{
    /*if (!isset($groupName)){
        $msg = xarML('Empty group_name (#(1)).', $groupName);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }*/

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_group_instances_table = $xartable['block_group_instances'];
    $block_instances_table = $xartable['block_instances'];
    $block_groups_table = $xartable['block_groups'];
    $block_types_table = $xartable['block_types'];

    // FIXME: Should use UNION instead of LEFT JOIN(?) - Paul
    $query = "SELECT    inst.xar_id as bid,
                        types.xar_type as type,
                        types.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        group_inst.xar_position as position,
                        groups.xar_template as _bl_template
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_groups_table as groups
              ON        group_inst.xar_group_id = groups.xar_id
              LEFT JOIN $block_instances_table as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $block_types_table as types
              ON        types.xar_id = inst.xar_type_id
              WHERE     groups.xar_name = '$groupName'
              AND       inst.xar_state > 0
              ORDER BY  group_inst.xar_position ASC";

    $result = $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return NULL;
    }

    $output = '';
    while(!$result->EOF) {
        $blockInfo = $result->GetRowAssoc(false);
        $blockInfo['last_update'] = $result->UnixTimeStamp($blockInfo['last_update']);

        $output .= xarBlock_render($blockInfo);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return NULL; // throw back exception
        }

        $result->MoveNext();
    }

    $result->Close();

    return $output;
}

?>
