<?php

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function modules_adminapi_getmenulinks()
{
    // Security Check
	if (xarSecurityCheck('AdminModules',0)) {
        // these links will only be shown to those who can admin the modules
        if(xarModGetUserVar('modules', 'expertlist')){
            $menulinks[] = Array('url'  => xarModURL('modules','admin','expertlist'),
                                'title' => xarML('View list of all installed modules on the system'),
                                'label' => xarML('View All'));
        }else{
            $menulinks[] = Array('url'  => xarModURL('modules','admin','list'),
                                'title' => xarML('View list of all installed modules on the system'),
                                'label' => xarML('View All'));
        }
        
/*         $menulinks[] = Array('url'  => xarModURL('modules','admin','prefs'), */
/*                             'title' => xarML('Set various options'), */
/*                             'label' => xarML('Preferences')); */
        
        $menulinks[] = Array('url'  => xarModURL('modules','admin','hooks'),
                            'title' => xarML('Extend the functionality of your modules via hooks'),
                            'label' => xarML('Configure Hooks'));
        
/*         $menulinks[] = Array('url'   => xarModURL('modules','admin','tools'), */
/*                              'title' => xarML('Use these tools to build and verify elements of modules.'), */
/*                              'label' => xarML('Toolbox')); */
    }

	if (xarSecurityCheck('EditModules',0)) {
        // others can see these links
        $menulinks[] = Array('url'   => xarModURL('modules','admin','release'),
                             'title' => xarML('View recent release information for certified modules within the last week.'),
                             'label' => xarML('Certified Releases'));
    }
    
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>