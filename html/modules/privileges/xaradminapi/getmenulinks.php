<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @return array containing the menulinks for the main menu items.
 */
function privileges_adminapi_getmenulinks()
{
    $menulinks = array();
    if (xarSecurityCheck('EditPrivilege',0)) {
        $menulinks[] = Array('url' => xarModURL('privileges','admin','overview'),
                               'title' => xarML('Privileges Overview'),
                              'label' => xarML('Overview'));

        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewprivileges',array('phase' => 'active')),
                              'title' => xarML('View all privileges on the system'),
                              'label' => xarML('View Privileges'));
    }

    if (xarSecurityCheck('AssignPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'newprivilege'),
                              'title' => xarML('Add a new privilege to the system'),
                              'label' => xarML('Add Privilege'));
    }

    if (xarSecurityCheck('ReadPrivilege',0,'Realm') && xarModGetVar('privileges','showrealms')) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewrealms'),
                              'title' => xarML('Add, change or delete realms'),
                              'label' => xarML('Manage Realms'));
    }

    if (xarSecurityCheck('AdminPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the privileges module configuration'),
                              'label' => xarML('Modify Config'));
    }
    return $menulinks;
}

?>
