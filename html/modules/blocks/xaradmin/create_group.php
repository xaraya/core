<?php

/**
 * create a new block group
 */
function blocks_admin_create_group()
{
    // Get parameters
    list($name,
         $template) = xarVarCleanFromInput('group_name',
                                          'group_template');

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

// Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Pass to API
    $group_id = xarModAPIFunc('blocks',
                             'admin',
                             'create_group', array('name'     => $name,
                                                   'template' => $template));

    // TODO: handle status messaging properly
    if ($group_id != false) {
        // Success
        xarSessionSetVar('statusmsg', xarML('Block group created.'));

        // Send to modify page to update group specifics
        xarResponseRedirect(xarModURL('blocks',
                            'admin',
                            'modify_group', array('gid' => $group_id)));

        return true;
    }

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>