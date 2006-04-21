<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return array containing the menulinks for the main menu items.
 */
function authsystem_adminapi_getmenulinks()
{
    $menulinks = array();

    if (xarSecurityCheck('AdminAuthsystem',0)) {
        $menulinks[] = Array('url'   => xarModURL('authsystem',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Modify the Authsystem authentication configuration'),
                              'label' => xarML('Modify Config'));
    }
    return $menulinks;
}

?>