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

function blocks_userapi_getinfo($args)
{
    $bid = 0;
    $name = NULL;
    extract($args);

    // Check parameters.
    if (empty($bid) && !is_numeric($bid) && empty($name)) {return;}

    if (!empty($bid) && xarVarIsCached('Block.Infos2', $bid)) {
        $instance = xarVarGetCached('Block.Infos2', $bid);
    } else {
        // Get the raw block instance data.
        $instance = xarModAPIfunc('blocks', 'user', 'get', array('bid' => $bid, 'name' => $name));
        // No matching block was found.
        if (empty($instance)) {return;}
        $bid = $instance['bid'];
        xarVarSetCached('Block.Infos2', $bid, $instance);
    }

    // TODO: Not sure what these are for.
    $instance['mid'] = $instance['module'];
    $instance['bkey'] = $instance['bid'];

    // The instance may be a member of 0, 1 or more groups.
    // Handle these instances slightly differently:
    // - if a group has been specified, just return that.
    // - if no group has been specified, take the single group if there is just one.
    // - otherwise raise return no group information.

    if (!empty($gid) && is_numeric($gid)) {
        // A group has been specified.
        if (isset($instance['groups'][$gid])) {
            $group = $instance['groups'][$gid];
        }
    } else {
        if (count($instance['groups']) == 1) {
            $group = array_pop($instance['groups']);
        }
    }
    unset($instance['groups']);

    // Legacy.
    $instance['id'] = $bid;

    // Choose the overriding template string.
    // In order (most significant first): group instance, instance, group
    // TODO: allow over-riding of inner and outer templates independantly.
    if (!empty($group)) {
        $template = !empty($group['group_inst_template']) ? $group['group_inst_template']
            : (!empty($instance['template']) ? $instance['template'] : $group['group_template']);
        $instance['group_name'] = $group['name'];
        $instance['group_id'] = $group['gid'];
    } else {
        $template = $instance['template'];
    }

    // Split template name into outer and inner using the ';' separator.
    if (strpos($template, ';') === FALSE) {
        $instance['_bl_box_template'] = $template;
        $instance['_bl_block_template'] = '';
    } else {
        $template = split(';', $template, 3);
        $instance['_bl_box_template'] = $template[0];
        $instance['_bl_block_template'] = $template[1];
        // TODO: what to do with the remainder?
    }

    // For legacy compatibility set the 'template' value to the box template.
    // No - new start: the box and the block templates are kept separate.
    //$instance['template'] = $instance['box_template'];

    return $instance;
}

?>
