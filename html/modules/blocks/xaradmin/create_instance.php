<?php

/**
 * create a new block instance
 */
function blocks_admin_create_instance()
{
    // Get parameters
    if (!xarVarFetch('block_type','str:1:',$type)) return;
    if (!xarVarFetch('block_group','str:1:',$group)) return;
    if (!xarVarFetch('block_state','str:1:',$state)) return;
    if (!xarVarFetch('block_title','str:1:',$title,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('block_template','str:1:',$template,'',XARVAR_NOT_REQUIRED)) return;

    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Pass to API
    $block_id = xarModAPIFunc('blocks',
                              'admin',
                              'create_instance', array('title'    => $title,
                                                       'type'     => $type,
                                                       'group'    => $group,
                                                       'template' => $template,
                                                       'state'    => $state));

    if (!$block_id) return;

    // Go on and edit the new instance
    xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $block_id)));

    return true;
}

?>