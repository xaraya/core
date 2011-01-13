<?php
/**
 * Pass individual menu items to the admin menu
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
 * Utility function pass individual menu items to the admin menu.
 *
 * @author the Modules module development team
 * @return array the menulinks for the admin menu items of this module.
 */
function modules_adminapi_getmenulinks()
{
    // No special menu. Just return links from xml file
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'modules', 'modtype' => 'admin'));
}

?>
