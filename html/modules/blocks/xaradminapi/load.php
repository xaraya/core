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
 * Load a block.
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param string modName the module name
 * @param string blockType the name of the block
 * @param string blockFunc the block function to load ('modify', 'display', 'info', 'help')
 * @return boolean success or failure
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function blocks_adminapi_load($args)
{
    // Array of block loaded flags.
    static $loaded = array();

    extract($args);

    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // Legacy - some modules still passing in a 'blockName'.
    if (!empty($blockName)) {$blockType = $blockName;}

    // These really are block types, as defined in the block_types.xar_type column.
    if (empty($blockType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }

    if (
        (isset($loaded[$modName . ':' . $blockType]) && empty($blockFunc))
        || (!empty($blockFunc) && isset($loaded[$blockFunc . '-' . $modName . ':' . $blockType]))
    ) {
        // The relevant files have already been loaded.
        return true;
    }

    // Details for the module.
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (empty($modBaseInfo)) {return;}

    // Directory holding the block scripts.
    $blockDir = 'modules/' . $modBaseInfo['osdirectory'] . '/xarblocks';

    // Load the block.
    // The base block file will always be loaded, and a more specific block
    // function will be loaded if available and requested.

    if (!isset($loaded[$modName . ':' . $blockType])) {
        // Load the block base script.

        $blockFile = $blockType . '.php';
        $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

        if (!file_exists($filePath)) {
            // TODO: should the block base be optional now?
            // i.e. do we really need to raise an error?
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $filePath);
            return;
        }
        include($filePath);
        $loaded[$modName . ':' . $blockType] = 1;

        // Load the block language files
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:blocks', $blockType) === NULL) { 
            return;
        }
    }

    if (!empty($blockFunc) && !isset($loaded[$blockFunc . '-' . $modName . ':' . $blockType])) {
        // Load the block function script, if available.

        $blockFile = $blockFunc . '-' . $blockType . '.php';
        $filePath = $blockDir . '/' . xarVarPrepForOS($blockFile);

        if (file_exists($filePath)) {
            include($filePath);
        }

        // Flag the script as loaded.
        $loaded[$blockFunc . '-' . $modName . ':' . $blockType] = 1;
    }

    return true;
}

?>