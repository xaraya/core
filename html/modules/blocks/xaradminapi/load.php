<?php
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
function blocks_adminapi_load($args)
{
    extract($args);

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

?>