<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
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
    $themeList = xarModAPIFunc('themes', 
                          'admin', 
                          'GetThemeList', 
                          array('filter'     => array('State' => XARTHEME_STATE_ANY)));
    //throw back
    if (!isset($themeList)) return;

    return $themeList;
}

?>
