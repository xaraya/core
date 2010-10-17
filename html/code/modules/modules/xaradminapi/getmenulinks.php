<?php
/**
 * utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules Module
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Modules module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function modules_adminapi_getmenulinks()
{
    // No special menu. Just return links from xml file
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'modules', 'modtype' => 'admin'));
}

?>
