<?php
/**
 * Pass individual menu items to the admin menu
 *
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * Utility function pass individual menu items to the admin menu.
 *
 * @return array the menulinks for the admin menu items of this module.
 */
function privileges_adminapi_getmenulinks()
{
    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','loadmenuarray',array('modname' => 'privileges', 'modtype' => 'admin'));
}
?>