<?php
/**
 * Utility function to pass individual menu items
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @return array the menulinks for the main menu items.
 */
function blocks_adminapi_getmenulinks()
{
    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'blocks', 'modtype' => 'admin'));
}

?>