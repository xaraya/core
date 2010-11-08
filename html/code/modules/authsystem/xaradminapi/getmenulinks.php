<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return array the menulinks for the admin menu items of this module.
 */
function authsystem_adminapi_getmenulinks()
{
    // No special menu. Just return links from xml
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'authsystem', 'modtype' => 'admin'));
}

?>