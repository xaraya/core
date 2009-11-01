<?php
/**
 * Get admin menu links
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Pass individual menu items to the admin menu
 *
 * @author the Base module development team
 * @return array containing the menulinks for the admin menu items.
 */
function base_adminapi_getmenulinks()
{
    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','menuarray',array('module' => 'base'));
}
?>
