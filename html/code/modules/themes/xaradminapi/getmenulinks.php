<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @author Marty Vance
 * @return array the menulinks for the admin menu items of this module.
 */
function themes_adminapi_getmenulinks()
{
    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'themes', 'modtype' => 'admin'));
}

?>