<?php
/** 
 * File: $Id$
 *
 * Load a block
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
 * Load a block
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param string modName the module name
 * @param string blockType the name of the block
 * @return bool
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function blocks_adminapi_load($args)
{
    extract($args);

    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    // Legacy - some modules still passing in a 'blockName'.
    if (!empty($blockName)) {$blockType = $blockName;}
    // These really are block types, as defined in the block_types.xar_type column.
    if (empty($blockType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }
    static $loaded = array();

    if (isset($loaded["$modName$blockType"])) {
        return true;
    }
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back exception

    $blockDir = 'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // Load the block
    $blockFile = $blockType . '.php';
    $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

    if (!file_exists($filePath)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $filePath);
        return;
    }
    include $filePath;
    $loaded["$modName$blockType"] = 1;

    // Load the block language files
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:blocks', $blockType) === NULL) return;

    // Initialise block (security schema) if required.
    $initFunc = "{$modName}_{$blockType}block_init";
    if (function_exists($initFunc)) {
        $initFunc();
    }
    return true;
}

?>
