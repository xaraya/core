<?php

/**
 * update a block
 */
function blocks_admin_update_instance()
{

    // Get parameters
    if (!xarVarFetch('bid','int:1:',$bid)) return;
    if (!xarVarFetch('block_group','str:1:',$group_id)) return;
    if (!xarVarFetch('block_state','str:1:',$state)) return;
    if (!xarVarFetch('block_title','str:1:',$title,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_template','str:1:',$template,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_content','str:1:',$content,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_refresh','str:1:',$refresh,'0',XARVAR_NOT_REQUIRED)) return;

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Get and update block info
    $blockinfo = xarBlockGetInfo($bid);
    $blockinfo['title'] = $title;
    $blockinfo['bid'] = $bid;
    $blockinfo['template'] = $template;
    $blockinfo['content'] = $content;
    $blockinfo['refresh'] = $refresh;
    $blockinfo['state'] = $state;
    // FIXME: this generates a Notice error
    if ($blockinfo['group_id'] != $group_id) {
        // Changed group, not worth keeping track of position, IMO
        $blockinfo['position'] = '0';
        $blockinfo['group_id'] = $group_id;
    }

    // Load block
    if (!xarBlock_Load($blockinfo['module'], $blockinfo['type'])) {
        return xarML('Block instance not found.');
    }

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
		global $HTTP_POST_VARS;
		$blockinfo = $func['func_update'](array_merge($HTTP_POST_VARS, $blockinfo));
	    }
        }
    }

    // Pass to API
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'update_instance',
                       $blockinfo)) return;

    // Resequence
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'resequence')) return;

    xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance',array('bid'=>$bid)));

    return true;
}

?>