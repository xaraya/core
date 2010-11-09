<?php
/**
 * utility function pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
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
