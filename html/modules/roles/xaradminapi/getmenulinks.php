<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function roles_adminapi_getmenulinks()
{

// Security Check
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'));
    }

// Security Check
    if (xarSecurityCheck('AddRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'newrole'),
                              'title' => xarML('Add a new user or group to the system'),
                              'label' => xarML('Add Group/User'));
    }

// Security Check
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'confirmationemail'),
                              'title' => xarML('Modify the confirmation email sent to new users'),
                              'label' => xarML('Confirmation Email'));
    }

// Security Check
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'welcomeemail'),
                              'title' => xarML('Modify the welcome email sent to new users'),
                              'label' => xarML('Welcome Email'));
    }

// Security Check
    if (xarSecurityCheck('AdminRole',0)) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the user module configuration'),
                              'label' => xarML('Modify Config'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>