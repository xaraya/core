<?php

function roles_userapi_getmenulinks()
{

    // get the admin part of the menu
    $menulinks = xarModAPIFunc('roles','admin','getmenulinks');

    // Security check
    if (true) {
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'user',
                                                  'view'),
                             'title' => xarML('View All Users'),
                             'label' => xarML('Memberslist'));
        if (xarUserIsLoggedIn()){
            $menulinks[] = Array('url'   => xarModURL('roles',
                                                      'user',
                                                      'account'),
                                 'title' => xarML('Your Custom Configuration'),
                                 'label' => xarML('Your Account'));
        }
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
    }


    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>