<?php

/**
 * update a block group
 */
function blocks_admin_update_group()
{
    // Get parameters
    if (!xarVarFetch('gid','int:1:',$gid)) return;
    if (!xarVarFetch('authid','str:1:',$authid)) return;
    if (!xarVarFetch('group_instance_order','str:1:',$group_instance_order,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('group_name','str:1:',$name)) return;
    if (!xarVarFetch('group_template','str:1:',$template,'',XARVAR_NOT_REQUIRED)) return;

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('EditBlock',0,'Instance')) return;

    // Pass to API
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'update_group', array('id' => $gid,
                                             'template' => $template,
                                             'name' => $name,
                                             'instance_order' => $group_instance_order))) return;
 
    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>
