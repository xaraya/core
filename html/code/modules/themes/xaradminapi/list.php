<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Obtain list of themes
 *
 * @author Marty Vance
 * @param none
 * @returns array
 * @return array of known themes
 * @throws NO_PERMISSION
 */
function themes_adminapi_list()
{
// Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    // Obtain information
    $themeList = xarMod::apiFunc('themes', 
                          'admin', 
                          'GetThemeList', 
                          array('filter'     => array('State' => XARTHEME_STATE_ANY)));
    //throw back
    if (!isset($themeList)) return;

    return $themeList;
}

?>
