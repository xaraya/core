<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function privileges_adminapi_getmenulinks()
{

// Security Check
	if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewprivileges&phase=active'),
                              'title' => xarML('View all privileges on the system'),
                              'label' => xarML('View Privileges'));
    }

// Security Check
	if (xarSecurityCheck('AssignRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'newprivilege'),
                              'title' => xarML('Add a new privilege to the system'),
                              'label' => xarML('Add Privilege'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>
