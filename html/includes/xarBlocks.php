<?php
/**
 * File: $Id$
 *
 * Display Blocks
 *
 * xarBlockType functions are now in xarLegacy,
 * they can be called through blocks module api
 *
 * @package blocks
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Display Blocks
 * @author Paul Rosania
 */


/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * @access protected
 * @param  array args
 * @param whatElseIsGoingLoaded integer
 * @returns bool
 * @todo    And why are you using $blockType instead of $blockName,
 *          when I said you to change I meant use $blockName everywhere,
 *          in the end it's the block name, not the block type, don't you think?
 */
function xarBlock_init($args, $whatElseIsGoingLoaded)
{
    // Blocks Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables = array('blocks'                => $systemPrefix . '_blocks',
                    'block_instances'       => $systemPrefix . '_block_instances',
                    'block_groups'          => $systemPrefix . '_block_groups',
                    'block_group_instances' => $systemPrefix . '_block_group_instances',
                    'block_types'           => $systemPrefix . '_block_types');

    xarDB_importTables($tables);

    return true;
}

/**
 * Get block information
 *
 * @access public
 * @param integer blockId  the block id
 * @return array block information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarBlockGetInfo($blockId)
{
    if ($blockId < 1) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'blockId');
        return;
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
                        type.xar_module as module,
                        type.xar_type as type,
                        groups.xar_name as group_name
              FROM      $blockInstancesTable as inst
              LEFT JOIN $blockGroupInstancesTable as group_inst
              ON        group_inst.xar_instance_id = inst.xar_id
              LEFT JOIN $blockTypesTable as type
              ON        type.xar_id = inst.xar_type_id
              LEFT JOIN $blockGroupsTable as groups
              ON        groups.xar_id = group_inst.xar_group_id
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

    return $blockInfo;
}

/**
 * Get block group information
 *
 * @access public
 * @param integer blockGroupId the block group id
 * @return array lock information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarBlockGroupGetInfo($blockGroupId)
{
    if ($blockGroupId < 1) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'blockGroupId');
        return;
    }

    list ($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockInstancesTable      = $tables['block_instances'];
    $blockTypesTable          = $tables['block_types'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockGroupInstancesTable = $tables['block_group_instances'];

    $query = "SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      $blockGroupsTable
              WHERE     xar_id = $blockGroupId";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group ID $blockGroupId not found.", $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $group = $result->GetRowAssoc(false);

    $result->Close();

    // Query for instances in this group
    $query = "SELECT    inst.xar_id as id,
                        types.xar_type as type,
                        types.xar_module as module,
                        inst.xar_title as title,
                        group_inst.xar_position as position
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as groups
              ON        group_inst.xar_group_id = groups.xar_id
              LEFT JOIN $blockInstancesTable as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable as types
              ON        types.xar_id = inst.xar_type_id
              WHERE     groups.xar_id = '$blockGroupId'
              ORDER BY  group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

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
 * Load a block
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @param string modName the module name
 * @param string blockType the name of the block
 * @return bool
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarBlock_load($modName, $blockName)
{
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($blockName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockName');
        return;
    }
    static $loaded = array();

    if (isset($loaded["$modName$blockName"])) {
        return true;
    }
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back exception

    $blockDir = 'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // Load the block
    $blockFile = $blockName . '.php';
    $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

    if (!file_exists($filePath)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $filePath);
        return;
    }
    include $filePath;
    $loaded["$modName$blockName"] = 1;

    // Load the block language files
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, XARMLS_CTXTYPE_BLOCK, $blockName) === NULL) return;

    // Initialise block (security schema) if required.
    $initFunc = "{$modName}_{$blockName}block_init";
    if (function_exists($initFunc)) {
        $initFunc();
    }
    return true;
}

/**
 * Load all blocks
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @return array blocks on success, false otherwise
 * @raise DATABASE_ERROR
 */
function xarBlock_loadAll()
{
    // Load blocks
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $modTable = $tables['modules'];

    $query = "SELECT xar_name,
                   xar_directory,
                   xar_regid
            FROM $modTable";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($name, $directory, $modId) = $result->fields;
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
                $infoFunc = $usname . '_' . $blockName . 'block_info';
                if (function_exists($infofunc)) {
                    $blocksModules["$name$blockName"] = $infoFunc();
                    $blocksModules["$name$blockName"]['bkey'] = $blockName;
                    $blocksModules["$name$blockName"]['mid'] = $modId;
                }
            }
        }
    }
    $result->Close();
    // Return information gathered
    return $blocksModules;
}

/**
 * Renders a block
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @param array blockInfo block information parameters
 * @return string output the block to show
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarBlock_render($blockInfo)
{
    $modName = $blockInfo['module'];
    $blockType = $blockInfo['type'];

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($blockType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }

    if (!xarBlock_load($modName, $blockType)) return;

    $displayFuncName = "{$modName}_{$blockType}block_display";

    // fetch complete blockinfo array
    if (function_exists($displayFuncName)) {
        $blockInfo = $displayFuncName($blockInfo);

        if (!isset($blockInfo)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back
            return '';
        }
        assert('is_array($blockInfo)');
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $displayFuncName);
        return;
    }

    // Determine which block box template to use
    $templateName = NULL;
    if (isset($blockInfo['_bl_template'])) {
        $templateName = $blockInfo['_bl_template'];
    }

    return xarTpl_renderBlockBox($blockInfo, $templateName);
}

/**
 * Renders a block group
 *
 * @author Paul Rosania, Marco Canini <m.canini@libero.it>
 * @access protected
 * @param string groupName the name of the block group
 * @return string
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function xarBlock_renderGroup($groupName)
{
    if (empty($groupName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'groupName');
        return;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    $blockGroupInstancesTable = $tables['block_group_instances'];
    $blockInstancesTable      = $tables['block_instances'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockTypesTable          = $tables['block_types'];

    $query = "SELECT    inst.xar_id as bid,
                        types.xar_type as type,
                        types.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        group_inst.xar_position as position,
                        groups.xar_template as _bl_template
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as groups
              ON        group_inst.xar_group_id = groups.xar_id
              LEFT JOIN $blockInstancesTable as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable as types
              ON        types.xar_id = inst.xar_type_id
              WHERE     groups.xar_name = '$groupName'
              AND       inst.xar_state > 0
              ORDER BY  group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $output = '';
    while(!$result->EOF) {
        $blockInfo = $result->GetRowAssoc(false);
        $blockInfo['last_update'] = $result->UnixTimeStamp($blockInfo['last_update']);

        $output .= xarBlock_render($blockInfo);
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back

        $result->MoveNext();
    }

    $result->Close();

    return $output;
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
    if (!xarModAPILoad('blocks', 'admin')) return;
    $args = array('modName'=>$modName, 'blockType'=>$blockType);
    return xarModAPIFunc('blocks', 'admin', 'register_block_type', $args);
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
    if (!xarModAPILoad('blocks', 'admin')) return;
    $args = array('modName'=>$modName, 'blockType'=>$blockType);
    return xarModAPIFunc('blocks', 'admin', 'unregister_block_type', $args);
}

?>