<?php

/**
 * create a new block instance
 */
function blocks_admin_create_instance()
{
    // Get parameters
    list($title,
         $type,
         $group,
         $template,
         $state)    = xarVarCleanFromInput('block_title',
                                          'block_type',
                                          'block_group',
                                          'block_template',
                                          'block_state');

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

    // TODO: handle status messaging properly
    if ($block_id != false) {
        // Success
        xarSessionSetVar('statusmsg', xarML('Block instance created.'));

        // Send to modify page to update block specifics
        xarResponseRedirect(xarModURL('blocks', 'admin', 'modify_instance', array('bid' => $block_id)));

        return true;
    }

    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>