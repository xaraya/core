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
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param array blockInfo block information parameters
 * @return string output the block to show
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarBlock_render($blockInfo)
{
    $modName = $blockInfo['module'];
    $blockType = $blockInfo['type'];
    $blockName = $blockInfo['name'];

    xarLogMessage("block rendering: module " . $modName . " / type " . $blockType);

    // This lets the security system know what module we're in
// no need to update / select in database for each block here
//    xarModSetVar('blocks','currentmodule',$modName);
    xarVarSetCached('Security.Variables','currentmodule',$modName);

    // Load the block.
    if (!xarModAPIFunc(
        'blocks', 'admin', 'load',
        array('modName' => $modName, 'blockType' => $blockType) )
    ) {return;}

    // Get the block display function name.
    $displayFuncName = "{$modName}_{$blockType}block_display";

    // Fetch complete blockinfo array.
    if (function_exists($displayFuncName)) {
        // Allow the block to modify the content before rendering.
        // In fact, the block can access and alter any aspect of the block info.
        $blockInfo = $displayFuncName($blockInfo);

        if (!isset($blockInfo)) {
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {return;} // throw back
            return '';
        }

        // FIXME: <mrb>
        // We somehow need to be able to raise exceptions here. We can't
        //       just ignore things which are wrong.
        // This would happen if a block does not return the blockinfo array correctly.
        if (!is_array($blockInfo)) {return '';}

        // Handle the new block templating style.
        // If the block has not done the rendering already, then render now.
        if (is_array($blockInfo['content'])) {
            // Here $blockInfo['content'] is template data.
            // Render this block template data.
            $blockInfo['content'] = xarTplBlock(
                $modName, $blockType, $blockInfo['content'],
                $blockInfo['_bl_block_template'], 
                !empty($blockInfo['_bl_template_base']) ? $blockInfo['_bl_template_base'] : NULL
            );
        }
    }

    // Now wrap the block up in a box.
    // TODO: pass the group name into this function (param 2?) for the template path.
    return xarTpl_renderBlockBox($blockInfo, $blockInfo['_bl_box_template']);
}

/**
 * Renders a block group
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
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

    $caching = 0;
    if (file_exists(xarCoreGetVarDirPath() . '/cache/output/cache.touch') && xarModGetVar('xarcachemanager','CacheBlockOutput')) {
        $caching = 1;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockGroupInstancesTable = $tables['block_group_instances'];
    $blockInstancesTable      = $tables['block_instances'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockTypesTable          = $tables['block_types'];

    // Fetch details of all blocks in the group.
    $query = "SELECT    inst.xar_id as bid,
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_name as name,
                        inst.xar_title as title,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        group_inst.xar_position as position,
                        bgroups.xar_name            AS group_name,
                        bgroups.xar_template        AS group_bl_template,
                        inst.xar_template           AS inst_bl_template,
                        group_inst.xar_template     AS group_inst_bl_template
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
    if (!$result) {return;}

    $output = '';
    while(!$result->EOF) {
        $blockInfo = $result->GetRowAssoc(false);

        if ($caching == 1) {
            $cacheKey = $blockInfo['module'] . "-blockid" . $blockInfo['bid'] . "-" . $groupName;
            $args = array('cacheKey' => $cacheKey, 'name' => 'block', 'blockid' => $blockInfo['bid']);
        }

        if ($caching == 1 && xarBlockIsCached($args)) {
            // output the cached block
            $output .= xarBlockGetCached($cacheKey,'block');

        } else {

            $blockInfo['last_update'] = $result->UnixTimeStamp($blockInfo['last_update']);

            // Get the overriding template name.
            // Levels, in order (most significant first): group instance, instance, group
            // TODO: allow over-riding of inner and outer templates at different levels independantly.
            $group_inst_bl_template = split(';', $blockInfo['group_inst_bl_template'], 3);
            $inst_bl_template = split(';', $blockInfo['inst_bl_template'], 3);
            $group_bl_template = split(';', $blockInfo['group_bl_template'], 3);

            if (empty($group_bl_template[0])) {
                // Default the box template to the group name.
                $group_bl_template[0] = $blockInfo['group_name'];
            }

            if (empty($group_bl_template[1])) {
                // Default the block template to the instance name.
                // TODO
                $group_bl_template[1] = $blockInfo['name'];
            }

            // Cascade level over-rides for the box template.
            $blockInfo['_bl_box_template'] = !empty($group_inst_bl_template[0]) ? $group_inst_bl_template[0]
                : (!empty($inst_bl_template[0]) ? $inst_bl_template[0] : $group_bl_template[0]);

            // Cascade level over-rides for the block template.
            $blockInfo['_bl_block_template'] = !empty($group_inst_bl_template[1]) ? $group_inst_bl_template[1]
                : (!empty($inst_bl_template[1]) ? $inst_bl_template[1] : $group_bl_template[1]);

            // Unset a few elements that clutter up the block details.
            // They are for internal use and we don't want them used within blocks.
            unset($blockInfo['group_inst_bl_template']);
            unset($blockInfo['inst_bl_template']);
            unset($blockInfo['group_bl_template']);
            
            $blockoutput = xarBlock_render($blockInfo);

            if ($caching == 1) {
                xarBlockSetCached($cacheKey, 'block', $blockoutput);
            }
            $output .= $blockoutput;

            // don't throw back exception for broken blocks
            //if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // throw back
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                $output .= xarExceptionRender('template');
                // We handled the exception(s) so we can clear it
                xarExceptionFree();
            }
        }

        // Next block in the group.
        $result->MoveNext();
    }

    $result->Close();

    return $output;
}

/**
 * Renders a single block
 *
 * @author John Cox
 * @access protected
 * @param string args[bid] contains id of block instance to render
 * @return string
 * @raise EMPTY_PARAM
 */
function xarBlock_renderBlock($args)
{
    extract($args);

    if (empty($bid)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'bid');
        return;
    }

    // If a block has more than one group, then the group needs to be specified.
    if (!isset($gid)) {
        $gid = NULL;
    }

    $blockInfo = xarModAPIFunc(
        'blocks', 'admin', 'getinfo', array('bid' => $bid, 'gid' => $gid)
    );

    $output = xarBlock_render($blockInfo);

    return $output;
}
?>
