<?php

/**
 * delete a block instance
 */
function blocks_admin_delete_instance()
{
    // Get parameters
    list($bid, $confirm) = xarVarCleanFromInput('bid', 'confirm');

// Security Check
	if(!xarSecurityCheck('DeleteBlock',0,'Instance')) return;

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current block
        $blockinfo = xarBlockGetInfo($bid);

        return array('instance' => $blockinfo,
                     'authid' => xarSecGenAuthKey());
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    xarModAPIFunc('blocks',
                  'admin',
                  'delete_instance', array('bid' => $bid));

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>