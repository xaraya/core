<?php

/**
 * create a new block group
 */
function blocks_admin_create_group()
{
    // Get parameters
    if (!xarVarFetch('group_name','str:1:',$name)) return;
    if (!xarVarFetch('group_template','str:1:',$template,'',XARVAR_NOT_REQUIRED)) return;

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Pass to API
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'create_group', array('name'     => $name,
                                             'template' => $template))) return;

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>