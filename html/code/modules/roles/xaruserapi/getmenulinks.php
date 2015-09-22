<?php
/**
 * Pass individual menu items to the user menu
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Utility function pass individual menu items to the user menu.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 * @return array the menulinks for the user menu items of this module.
 */
function roles_userapi_getmenulinks()
{
    //If we have turned on role list (memberlist) display and users have requisite level to see them
    $menulinks = array();
    if ((bool)xarModVars::get('roles', 'displayrolelist')){
            $menulinks[] = array(
                'url'   => xarModURL('roles','user','view'),
                'title' => xarML('View All Users'),
                'label' => xarML('Memberslist'),
                'active' => array('view'),
                );
    }
    if (xarUserIsLoggedIn()){
        $menulinks[] = array(
            'url'   => xarModURL('roles','user','account'),
            'title' => xarML('Your Custom Configuration'),
            'label' => xarML('Your Account'),
            'active' => array('account'),
            );
    }
    return $menulinks;
}
?>
