<?php
/** 
 * File: $Id$
 *
 * Update a block
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
 * update a block
 */
function blocks_admin_update_instance()
{
    // Get parameters
    if (!xarVarFetch('bid', 'int:1:', $bid)) {return;}
    if (!xarVarFetch('block_groups', 'keylist:id;checkbox', $block_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_new_group', 'id', $block_new_group, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_remove_groups', 'keylist:id;checkbox', $block_remove_groups, array(), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:field:Name:passthru:str:1:100', $name)) {return;}
    if (!xarVarFetch('block_title', 'str:1:255', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_state', 'int:0:4', $state)) {return;}
    if (!xarVarFetch('block_template', 'strlist:;,:pre:trim:lower:ftoken', $block_template, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('group_templates', 'keylist:id;strlist:;,:pre:trim:lower:ftoken', $group_templates, array(), XARVAR_NOT_REQUIRED)) {return;}
    // TODO: deprecate 'block_content' - make sure each block handles its own content entirely.
    if (!xarVarFetch('block_content', 'str:1:', $content, NULL, XARVAR_NOT_REQUIRED)) {return;}
    // TODO: check out where 'block_refresh' is used. Could it be used more effectively?
    // Could the caching be supported in a more consistent way, so individual blocks don't
    // need to handle it themselves?
    if (!xarVarFetch('block_refresh', 'int:0:', $refresh, '0', XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Security Check.
    if (!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Get and update block info.
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('bid' => $bid));

    // If the name is being changed, then check the new name has not already been used.
    if ($blockinfo['name'] != $name) {
        $checkname = xarModAPIFunc('blocks', 'user', 'get', array('name' => $name));
        if (!empty($checkname)) {
            $msg = xarML('Block name "#(1)" already exists', $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        }
    }
    $blockinfo['name'] = $name;
    $blockinfo['title'] = $title;
    $blockinfo['template'] = $block_template;
    $blockinfo['refresh'] = $refresh;
    $blockinfo['state'] = $state;

    if (isset($content)) {
        $blockinfo['content'] = $content;
    }

    // Pick up the block instance groups and templates.
    $groups = array();
    foreach($block_groups as $gid => $block_group) {
        // Set the block group so long as the 'remove' checkbox is not set.
        if (!isset($block_remove_groups[$gid]) || $block_remove_groups[$gid] == false) {
            $groups[] = array(
                'gid' => $gid,
                'template' => $group_templates[$gid]
            );
        }
    }
    // The block was added to a new block group using the drop-down.
    if (!empty($block_new_group)) {
        $groups[] = array(
            'gid' => $block_new_group,
            'template' => ''
        );
    }
    $blockinfo['groups'] = $groups;

    // Load block
    if (!xarModAPIFunc(
            'blocks', 'admin', 'load',
            array(
                'module' => $blockinfo['module'],
                'type' => $blockinfo['type'],
                'func' => 'modify'
            )
        )
    ) {return;}

    // Do block-specific update
    $usname = preg_replace('/ /', '_', $blockinfo['module']);
    $updatefunc = $usname . '_' . $blockinfo['type'] . 'block_update';

    if (function_exists($updatefunc)) {
        $blockinfo = $updatefunc($blockinfo);
    } else {
        $blockinfofunc = $usname . '_' . $blockinfo['type'] . 'block_info';
        $blockdesc = $blockinfofunc();
        if (!empty($blockdesc['func_update'])) {
            $updatefunc = $blockdesc['func_update'];
            if (function_exists($updatefunc)) {
                $blockinfo = $updatefunc($blockinfo);
            }
        }
    }

    // If the update function failed to return the blockinfo array, then
    // throw the error back (if there is an error).
    if (!is_array($blockinfo)) {
        if (!xarCurrentErrorType()) {
            // Raise an error here, since no error has been raised in 
            // the block update function.
            $msg = xarML('Unknown error in block update function "#(1)"', $updatefunc);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        }
        return; 
    }

    // Pass to API - do generic updates.
    if (!xarModAPIFunc('blocks', 'admin', 'update_instance', $blockinfo)) {return;}

    // Resequence blocks within groups.
    if (!xarModAPIFunc('blocks', 'admin', 'resequence')) {return;}

    xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $bid)));

    return true;
}

?>