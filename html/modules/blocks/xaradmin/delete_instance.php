<?php

/**
 * delete a block instance
 */
function blocks_admin_delete_instance()
{
    // Get parameters
    if (!xarVarFetch('bid','int:1:',$bid)) return;
    if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;

    // Security Check
	if(!xarSecurityCheck('DeleteBlock',0,'Instance')) return;

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current block
        $blockinfo = xarModAPIFunc('blocks', 
                                   'admin', 
                                   'getinfo', array('blockId' => $bid));

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