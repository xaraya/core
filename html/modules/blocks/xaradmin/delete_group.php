<?php

/**
 * delete a block group
 */
function blocks_admin_delete_group()
{
// Security Check
	if(!xarSecurityCheck('DeleteBlock',0,'Instance')) return;

    // Get parameters
    list($gid, $confirm) = xarVarCleanFromInput('gid', 'confirm');

    // Check for confirmation
    if (empty($confirm)) {
        // No confirmation yet - get one

        // Get details on current group
        $group = xarBlockGroupGetInfo($gid);
        if ($group == NULL) {
            return;
        }

        return array('group' => $group,
                     'authid' => xarSecGenAuthKey());
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