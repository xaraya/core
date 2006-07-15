<?php
/**
 * utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules Module
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Modules module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function modules_adminapi_getmenulinks()
{
    // Security Check
    $menulinks = array();
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

        $menulinks[] = Array('url'  => xarModURL('modules','admin','modifyconfig'),
                            'title' => xarML('Modify configuration parameters'),
                            'label' => xarML('Modify config'));
    }
    return $menulinks;
}

?>
