<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @author the Base module development team
 * @return array the menulinks for the admin menu items of this module.
 */
function base_adminapi_getmenulinks()
{

    // All modules which supply links in xml should get their links from the loadmenuarray function
    $menulinks = xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'base', 'modtype' => 'admin'));

    /*
    This menu gets its data from the adminmenu-dat.xml file in /code/modules/base/xardata/
    You can add or change menu items by changing the data in that file.
    Or you can create your own menu items here. They should have the form of this example:
    $menulinks = array();

    .....
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = array(
            'url'   => xarModURL('roles','admin','viewroles'),          // link url
            'title' => xarML('View and edit the groups on the system'), // link title
            'label' => xarML('View All Groups'),                        // link label
            'active' => array('viewroles'),                             // array of active function names
        );
    }
    .....
    */

    return $menulinks;
}
?>
