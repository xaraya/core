<?php
/**
 * Utility to get menu links
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('AdminDynamicData',0)) {

        $menulinks[] = Array('url' => xarModURL('dynamicdata','admin','overview'),
                               'title' => xarML('DynamicData Overview'),
                              'label' => xarML('Overview'));

        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                   'admin',
                                                   'view'),
                              'title' => xarML('View module objects using dynamic data'),
                              'label' => xarML('View Objects'));
    }
    if (xarSecurityCheck('AdminDynamicData',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Configure the default property types'),
                              'label' => xarML('Property Types'));
    }
    if (xarSecurityCheck('AdminDynamicData',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'admin',
                                                  'utilities'),
                              'title' => xarML('Import/export and other utilities'),
                              'label' => xarML('Utilities'));
    }
    return $menulinks;
}
?>
