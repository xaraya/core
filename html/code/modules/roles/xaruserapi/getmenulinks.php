<?php
/**
 * Pass individual menu items to the user menu
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Utility function pass individual menu items to the user menu.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> the menulinks for the user menu items of this module.
 */
function roles_userapi_getmenulinks()
{
    //If we have turned on role list (memberlist) display and users have requisite level to see them
    $menulinks = array();
    if ((bool)xarModVars::get('roles', 'displayrolelist')){
            $menulinks[] = array(
                'url'   => xarController::URL('roles','user','view'),
                'title' => xarML('View All Users'),
                'label' => xarML('Memberslist'),
                'active' => array('view'),
                );
    }
    if (xarUser::isLoggedIn()){
        $menulinks[] = array(
            'url'   => xarController::URL('roles','user','account'),
            'title' => xarML('Your Custom Configuration'),
            'label' => xarML('Your Account'),
            'active' => array('account'),
            );
    }
    return $menulinks;
}
