<?php

/**
 * update a block
 */
function blocks_admin_update_instance()
{
    // Get parameters
    list($bid,
         $title,
         $template,
         $content,
         $refresh,
         $state,
         $group_id) = xarVarCleanFromInput('bid',
                                          'block_title',
                                          'block_template',
                                          'block_content',
                                          'block_refresh',
                                          'block_state',
                                          'block_group');

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

// Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // FIXME: the whole refresh thing seems to need some clean-up :)
    if (!isset($refresh)) {
        $refresh = 0;
    }

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
        $resequence = 1;
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
    if (xarModAPIFunc('blocks',
                     'admin',
                     'update_instance',
                     $blockinfo)) {
        // Success
        xarSessionSetVar('statusmsg', xarML('Block instance updated.'));

        if (!empty($resequence)) {
            // Also need to resequence
            xarModAPIFunc('blocks', 'admin', 'resequence');
        }
    }
		// Return
    xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance',array('bid'=>$bid)));

    return true;
}

?>