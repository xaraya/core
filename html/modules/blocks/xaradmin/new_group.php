<?php

/**
 * display form for a new block group
 */
function blocks_admin_new_group()
{
// Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    // Include 'formcheck' JavaScript.
    // TODO: move this to a template widget when available.
    xarModAPIfunc(
        'base', 'javascript', 'modulefile',
        array('module'=>'base', 'filename'=>'formcheck.js')
    );

    return array('createlabel' => xarML('Create Group'),
                 'cancellabel' => xarML('Cancel'));
}

?>