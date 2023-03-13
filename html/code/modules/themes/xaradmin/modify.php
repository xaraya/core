<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */
/**
 * Modify theme settings
 *
 * This function queries the database for
 * the theme's information.
 *
 * @author Marty Vance 
 * @param int id $ theme id
 * @return array|string|void data for the template display
 */
function themes_admin_modify()
{
    if (!xarVar::fetch('id', 'int:1:', $regId, 0, xarVar::NOT_REQUIRED)) return;
    if (empty($regId)) return xarResponse::notFound();

    $themeInfo = xarTheme::getInfo($regId); 
    // throw back
    if (!isset($themeInfo)) return;

    $themeName = $themeInfo['name'];
    
    // Security
    if (!xarSecurity::check('AdminThemes', 0, 'All', '$themeName::$regId')) return;

    $themevars = array();
    //xarTheme::getVarsByTheme($themeName);

    $displayInfo = array();
    foreach($themeInfo as $k => $v) {
        $displayInfo[] = array('name' => $k, 'value' => $v);
    } 
    // End form
    $data['authid'] = xarSec::genAuthKey();
    $data['id'] = $regId;
    $data['name'] = $themeInfo['name'];
    $data['themeInfo'] = $displayInfo;
    $data['themevars'] = $themevars;
    $data['savebutton'] = xarML('Save Changes');

    return $data;
}
