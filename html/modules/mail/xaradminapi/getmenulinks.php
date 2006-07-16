<?php
/**
 * Pass individual menu items to the admin panels
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * utility function pass individual menu items to the admin panels
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function mail_adminapi_getmenulinks()
{
	/*
	This menu gets its data from the adminmenu.php file in the module's xardataapi folder.
	You can add or change menu items by changing the data there.
	Or you can create your own menu items here. They should have the form of this example:

    $menulinks = array();
    .....
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'));
    }
    .....
    return $menulinks;
    */

    $menulinks = xarModAPIFunc('base','admin','menuarray');
	if (xarModIsAvailable('scheduler')) {
		$menulinks[] = array('url' => xarModURL('mail','admin','viewq'),
							 'title' => xarML('View all mails scheduled to be sent later'),
							 'label' => xarML('View Mail Queue'));
	}
    return $menulinks;
}
?>
