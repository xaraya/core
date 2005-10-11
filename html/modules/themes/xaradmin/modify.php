<?php
/**
 * Modify theme settings
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Modify theme settings
 *
 * This function queries the database for
 * the theme's information.
 *
 * @author Marty Vance 
 * @param id $ theme id
 * @returns array
 * @return an array of variables to pass to the template
 */
function themes_admin_modify()
{
    if (!xarVarFetch('id', 'int:1:', $regId)) return;

    $themeInfo = xarThemeGetInfo($regId); 
    // throw back
    if (!isset($themeInfo)) return;

    $themeName = $themeInfo['name'];
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '$themeName::$regId')) return;

    $themevars = xarTheme_getVarsByTheme($themeName);

    $displayInfo = array();
    foreach($themeInfo as $k => $v) {
        $displayInfo[] = array('name' => $k, 'value' => $v);
    } 
    // End form
    $data['authid'] = xarSecGenAuthKey();
    $data['id'] = $regId;
    $data['name'] = $themeInfo['name'];
    $data['themeInfo'] = $displayInfo;
    $data['themevars'] = $themevars;
    $data['savebutton'] = xarML('Save Changes');

    return $data;
} 

?>
