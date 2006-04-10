<?php
/**
 * Standard function to get main menu links
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/* 
 * Standard function to get main menu links
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_userapi_getmenulinks()
{
    //If we have turned on role list (memberlist) display and users have requisite level to see them
    if (xarModGetVar('roles', 'displayrolelist') && xarSecurityCheck('ViewRoles',0) ){
        if (true) {
            $menulinks[] = Array('url'   => xarModURL('roles',
                                                      'user',
                                                      'view'),
                                 'title' => xarML('View All Users'),
                                 'label' => xarML('Memberslist'));

        }
    }
    if (true) {
        if (xarUserIsLoggedIn()){
            $menulinks[] = Array('url'   => xarModURL('roles','user','account'),
                                 'title' => xarML('Your Custom Configuration'),
                                 'label' => xarML('Your Account'));
        }
    }
    //jojodee- Moved to Registration Module. Needed for reading/checking when registering. Most sites will require these
    //Can still be provided with custom pages, or install Registration module and turn registration off if you don't need it.
    // we don't really want to introduce dependency here on non-core module and poll for registration allowed.
    /*
    if (xarModGetVar('roles', 'showprivacy')){
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'user',
                                                  'privacy'),
                             'title' => xarML('Privacy Policy for this Website'),
                             'label' => xarML('Privacy Policy'));
    }
    if (xarModGetVar('roles', 'showterms')){
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'user',
                                                  'terms'),
                             'title' => xarML('Terms of Use for this website'),
                             'label' => xarML('Terms of Use'));
    }
    */
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>