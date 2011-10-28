<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Modify theme settings
 *
 * This function queries the database for
 * the theme's information.
 *
 * @author Marty Vance 
 * @param id $ theme id
 * @return array data for the template display
 */
function themes_admin_modify()
{
    if (!xarVarFetch('id', 'int:1:', $regId, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($regId)) return xarResponse::notFound();

    $themeInfo = xarThemeGetInfo($regId); 
    // throw back
    if (!isset($themeInfo)) return;

    $themeName = $themeInfo['name'];
    
    // Security
    if (!xarSecurityCheck('AdminThemes', 0, 'All', '$themeName::$regId')) return;

    $themevars = array();
    //xarTheme_getVarsByTheme($themeName);

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
