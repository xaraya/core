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
    if (!xarVarFetch('block_name', 'pre:lower:ftoken:field:Name:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('block_title', 'str:1:', $title, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_state', 'str:1:', $state)) {return;}
    if (!xarVarFetch('block_template', 'strlist:;,:pre:trim:lower:ftoken', $block_template, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('group_templates', 'keylist:id;strlist:;,:pre:trim:lower:ftoken', $group_templates, array(), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_content', 'str:1:', $content, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('block_refresh', 'str:1:', $refresh, '0', XARVAR_NOT_REQUIRED)) {return;}

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Security Check.
	if(!xarSecurityCheck('AddBlock', 0, 'Instance')) {return;}

    // Get and update block info.
    $blockinfo = xarModAPIFunc('blocks', 'user', 'get', array('bid' => $bid));
    $blockinfo['name'] = $name;
    $blockinfo['title'] = $title;
    $blockinfo['template'] = $block_template;
    $blockinfo['content'] = $content;
    $blockinfo['refresh'] = $refresh;
    $blockinfo['state'] = $state;

    // Pick up the block instance groups and templates.
    $groups = array();
    foreach($block_groups as $gid => $block_group) {
        $groups[] = array(
            'gid' => $gid,
            'template' => $group_templates[$gid]
        );
    }
    $blockinfo['groups'] = $groups;

    // Load block
    if (!xarModAPIFunc(
            'blocks', 'admin', 'load',
            array(
                'modName' => $blockinfo['module'],
                'blockName' => $blockinfo['type'],
                'blockFunc' => 'modify'
            )
        )
    ) {return;}

    // Do block-specific update
    $usname = preg_replace('/ /', '_', $blockinfo['module']);
    $updatefunc = $usname . '_' . $blockinfo['type'] . 'block_update';

    if (function_exists($updatefunc)) {
        $blockinfo = $updatefunc($blockinfo);
    } else {
	$updatefunc = $usname . '_' . $blockinfo['type'] . 'block_info';
	$func = $updatefunc();
	if(!empty($func['func_update'])) {
	    if (function_exists($func['func_update'])) {
                // TODO: this doesn't look right. Is there a function the get/post vars
                // go through before being used?
		global $HTTP_POST_VARS;
		$blockinfo = $func['func_update'](array_merge($HTTP_POST_VARS, $blockinfo));
	    }
        }
    }

    // Pass to API - do generic updates.
    if (!xarModAPIFunc('blocks', 'admin', 'update_instance', $blockinfo)) {return;}

    // Resequence.
    if (!xarModAPIFunc('blocks', 'admin', 'resequence')) {return;}

    xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance',array('bid'=>$bid)));

    return true;
}

?>