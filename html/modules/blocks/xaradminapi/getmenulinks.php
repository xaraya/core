<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function blocks_adminapi_getmenulinks()
{

// Security Check
	if (xarSecurityCheck('EditBlock',0)) {

        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'view_types'),
                              'title' => xarML('View the different registered block types available'),
                              'label' => xarML('View Types'));
    }

// Security Check
	if (xarSecurityCheck('EditBlock',0)) {

        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'view_instances'),
                              'title' => xarML('View or edit all block instances'),
                              'label' => xarML('View Instances'));
    }

// Security Check
	if (xarSecurityCheck('EditBlock',0)) {

        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'view_groups'),
                              'title' => xarML('View or edit all block groups'),
                              'label' => xarML('View Groups'));
    }

// Security Check
	if (xarSecurityCheck('AddBlock',0)) {

        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'new_instance'),
                              'title' => xarML('Add a new block instance'),
                              'label' => xarML('Add Instance'));
    }

// Security Check
	if (xarSecurityCheck('AddBlock',0)) {

        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                   'admin',
                                                   'new_group'),
                              'title' => xarML('Add a new group of blocks'),
                              'label' => xarML('Add Group'));
    }

/* Removed the collapsing blocks.  Need a better way to do this.
	if (xarSecurityCheck('AdminBlock',0)) {
        $menulinks[] = Array('url'   => xarModURL('blocks',
                                                  'admin',
                                                  'settings'),
                              'title' => xarML('Modify your blocks module configuration'),
                              'label' => xarML('Modify Config'));
    }
*/
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>