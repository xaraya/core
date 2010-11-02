<?php
/**
 * Pass individual menu items to the main menu
 *
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 */
/**
* utility function pass individual menu items to the main menu
*
* @author Marty Vance
* @returns array
* @return array containing the menulinks for the main menu items.
*/
function themes_adminapi_getmenulinks()
{
    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'themes', 'modtype' => 'admin'));
}

?>