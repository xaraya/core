<?php

/**
 * update a block group
 */
function blocks_admin_update_group()
{
    // Get parameters
    list($authid,
         $gid,
         $group_instance_order,
         $template,
         $name) = $foo = xarVarCleanFromInput('authid',
                                      'gid',
                                      'group_instance_order',
                                      'group_template',
                                      'group_name');

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

// Security Check
	if(!xarSecurityCheck('EditBlock',0,'Instance')) return;

    // Pass to API
    xarModAPIFunc('blocks',
                  'admin',
                  'update_group', array('id' => $gid,
                                        'template' => $template,
                                        'name' => $name,
                                        'instance_order' => $group_instance_order));
 
   xarResponseRedirect(xarModURL('blocks', 'admin', 'view_groups'));

    return true;
}

?>