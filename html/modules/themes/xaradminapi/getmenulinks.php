<?php
/**
 * File: $Id$
 *
 * Utility function to pass individual menu items to the main menu
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
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


    // addition by sw@telemedia.ch (Simon Wunderlin)
    // as per http://bugs.xaraya.com/show_bug.cgi?id=1162
    // added and commited by <andyv>
    // TODO: add credits in changelist.. John?
    if (xarSecurityCheck('AdminTheme',0)) {

        $menulinks[] = Array('url'   => xarModURL('themes',
                                                  'admin',
                                                  'listtpltags'),
                              'title' => xarML('View the registered template tags.'),
                              'label' => xarML('Template Tags'));
	}
	if (xarSecurityCheck('AdminTheme',0)) {

        $menulinks[] = Array('url'   => xarModURL('themes',
                                                   'admin',
                                                   'modifyconfig'),
                              'title' => xarML('Modify the configuration of the themes module'),
                              'label' => xarML('Modify Config'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>
