<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Standard function to get main menu links
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_userapi_getmenulinks()
{
    //If we have turned on role list (memberlist) display and users have requisite level to see them
    $menulinks = array();
    if (xarModVars::get('roles', 'displayrolelist')){
            $menulinks[] = array('url'   => xarModURL('roles',
                                                      'user',
                                                      'view'),
                                 'title' => xarML('View All Users'),
                                 'label' => xarML('Memberslist'));

    }
    if (xarUserIsLoggedIn()){
        $menulinks[] = array('url'   => xarModURL('roles','user','account'),
                             'title' => xarML('Your Custom Configuration'),
                             'label' => xarML('Your Account'));
    }
    return $menulinks;
}
?>
