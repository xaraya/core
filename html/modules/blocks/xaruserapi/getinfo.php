<?php
/** 
 * File: $Id$
 *
 * Get details suitable for *rendering* a block instance.
 * This will return the details for a block.
 *
 * TODO: big changes planned for in here.
 * - references to groups will be removed
 * - block can be an instance or a 'one-off' standalone block
 * - standalone blocks will be seeded with the block init details (if available)
 * - arbitrary parameters can be passed in to override the block content array elements
 * - some sort of validation check could be made available for the overridable params?
 * - system-level flag to switch between reporting attribute/args errors or just ignoring
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/*
 * Note: this function is used solely by the BL renderer, and is subject
 * to change without notice.
 */

function blocks_userapi_getinfo($args)
{
    extract($args);

    // We will be selecting either a block instance or a stand-alone block.
    if (!empty($instance)) {
        // Block instance - fetch it from the database.
        if (is_numeric($instance)) {
            if (xarVarIsCached('Block.Infos2', $instance)) {
                $blockinfo = xarVarGetCached('Block.Infos2', $instance);
            } else {
                $blockinfo = xarModAPIfunc('blocks', 'user', 'get', array('bid' => $instance));
            }

            if (empty($blockinfo)) {
                // No matching block was found.
                return;
            }

            if (!xarVarIsCached('Block.Infos2', $blockinfo['bid'])) {
                // Cache the block details if available
                xarVarSetCached('Block.Infos2', $blockinfo['bid'], $blockinfo);
            }
        } else {
            $blockinfo = xarModAPIfunc('blocks', 'user', 'get', array('name' => $instance));
        }
    } else {
        // Standalone block - load it from file and seed with default details.
        // TODO: under development.
        return;
    }

    // Split template name into outer and inner using the ';' separator.
    if (strpos($template, ';') === FALSE) {
        $blockinfo['_bl_box_template'] = $template;
        $blockinfo['_bl_block_template'] = '';
    } else {
        $template = split(';', $template, 3);
        $blockinfo['_bl_box_template'] = $template[0];
        $blockinfo['_bl_block_template'] = $template[1];
    }

    // Legacy support.
    $instance['id'] = $blockinfo['bid'];
    $blockinfo['bkey'] = $blockinfo['bid'];
    $blockinfo['mid'] = $blockinfo['module'];

    return $blockinfo;
}

?>