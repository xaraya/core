<?php

/**
 * display form for a new block group
 */
function blocks_admin_new_group()
{
// Security Check
	if(!xarSecurityCheck('AddBlock',0,'Instance')) return;

    return array('createlabel' => xarML('Create Group'),
                 'cancellabel' => xarML('Cancel'));
}

?>