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
 * @author Paul Rosania
 */


/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * @access protected
 * @param  array args
 * @param  whatElseIsGoingLoaded integer
 * @return bool
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

// this lets the security system know what module we're in
// no need to update / select in database for each block here
//    xarModSetVar('blocks','currentmodule',$modName);
    xarVarSetCached('Security.Variables','currentmodule',$modName);

/* Lets get rid of these for a bit.  Blocks shouldn't kill a site.
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($blockType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }
*/

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'load', array('modName' => $modName,
                                     'blockName' => $blockType))) return;

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
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_template as template,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        group_inst.xar_position as position,
                        bgroups.xar_template as bgroups_bl_template,
                        inst.xar_template as inst_bl_template
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as bgroups
              ON        group_inst.xar_group_id = bgroups.xar_id
              LEFT JOIN $blockInstancesTable as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable as btypes
              ON        btypes.xar_id = inst.xar_type_id
              WHERE     bgroups.xar_name = '$groupName'
              AND       inst.xar_state > 0
              ORDER BY  group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $output = '';
    while(!$result->EOF) {
        $blockInfo = $result->GetRowAssoc(false);
        $blockInfo['last_update'] = $result->UnixTimeStamp($blockInfo['last_update']);

    if (!empty($blockInfo['inst_bl_template'])) {
        $blockInfo['_bl_template'] = $blockInfo['inst_bl_template'];
    } else {
        $blockInfo['_bl_template'] = $blockInfo['bgroups_bl_template'];
    }

        $output .= xarBlock_render($blockInfo);

// don't throw back exception for broken blocks
//        if (xarExceptionMajor() != XAR_NO_EXCEPTION) return; // throw back
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            $output .= xarExceptionRender('html');
            // We handled the exception(s) so we can clear it
            xarExceptionFree();
        }

        $result->MoveNext();
    }

    $result->Close();

    return $output;
}

/**
 * Renders an individual block
 *
 * @author John Cox
 * @access protected
 * @param string blockInfo contains id of block to render
 * @return string
 * @raise EMPTY_PARAM
 */
function xarBlock_renderBlock($blockInfo)
{
    $blockID = $blockInfo['bid'];
    if (empty($blockID)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockID');
        return;
    }

    $blockInfo = xarModAPIFunc('blocks',
                               'admin',
                               'getInfo', array('blockId' => $blockID));

    $output = xarBlock_render($blockInfo);

    return $output;
}

?>