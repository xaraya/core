<?php

/**
 * delete a block group
 */
function blocks_admin_delete_group()
{
// Security Check
	if(!xarSecurityCheck('DeleteBlock',0,'Instance')) return;

    if (!xarVarFetch('gid','int:1:',$gid)) return;
    if (!xarVarFetch('confirm','str:1:',$confirm,'',XARVAR_NOT_REQUIRED)) return;

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current group
        $group = xarModAPIFunc('blocks', 
                               'admin', 
                               'groupgetinfo', array('blockGroupId' => $gid));

        if ($group == NULL) return;

        return array('group' => $group,
                     'authid' => xarSecGenAuthKey(),
                     'deletelabel' => xarML('Delete'));
    }

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    xarModAPIFunc('blocks',
                  'admin',
                  'delete_group', array('gid' => $gid));

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>