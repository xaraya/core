<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage authsystem module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
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
    // No special menu. Just return links from xml
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'authsystem', 'modtype' => 'admin'));
}

?>