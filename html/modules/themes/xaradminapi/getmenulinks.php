<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function themes_adminapi_getmenulinks()
{

// Security Check
	if (xarSecurityCheck('AdminTheme',0)) {

        $menulinks[] = Array('url'   => xarModURL('themes',
                                                   'admin',
                                                   'list'),
                              'title' => xarML('View installed themes on the system'),
                              'label' => xarML('View Themes'));
    }

    $data['authid'] = xarSecGenAuthKey();

// Security Check
	if (xarSecurityCheck('AdminTheme',0)) {

        $menulinks[] = Array('url'   => xarModURL('themes',
                                                   'admin',
                                                   'modifyconfig'),
                              'title' => xarML('Modify the configuration of the themes module'),
                              'label' => xarML('Modify Config'));
    }

	if (xarSecurityCheck('AdminTheme',0)) {

        $menulinks[] = Array('url'   => xarModURL('themes',
                                                  'admin',
                                                  'release'),
                              'title' => xarML('View recent release information for certified themes within the last week.'),
                              'label' => xarML('Certified Releases'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>
