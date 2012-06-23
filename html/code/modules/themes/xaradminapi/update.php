<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Update theme information
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id number of the theme to update<br/>
 *        string   $args['displayname'] the new display name of the theme<br/>
 *        string   $args['description'] the new description of the theme
 * @return boolean true on success, false on failure
 */
function themes_adminapi_update(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (!isset($themeInfo)) {
        throw new ThemeNotFoundException($regid,'Theme (regid: #(1) does not exist.');
    }
    $themename = $themeInfo['name'];

    // Security Check
    if (!xarSecurityCheck('AdminThemes',0,'All',"All:All:$regId")) return;

    $themeInfo = xarMod::getBaseInfo($themename, 'theme');

    return true;
}

?>