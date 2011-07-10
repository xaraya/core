<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array the menulinks for the admin menu items of this module.
 */
function roles_adminapi_getmenulinks()
{
    /*
    This menu gets its data from the adminmenu.php file in the module's xardataapi folder.
    You can add or change menu items by changing the data there.
    Or you can create your own menu items here. They should have the form of this example:

    $menulinks = array();
    .....
    if (xarSecurityCheck('EditRoles',0)) {
        $menulinks[] = array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'),
                              'active' => array('viewroles'),
                       );
    }
    .....
    return $menulinks;
    */

    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'roles', 'modtype' => 'admin'));
}

?>