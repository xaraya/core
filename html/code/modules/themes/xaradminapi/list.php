<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Obtain list of themes
 *
 * @author Marty Vance
 * @return array the known themes
 * @throws NO_PERMISSION
 */
function themes_adminapi_list()
{
// Security Check
    if(!xarSecurityCheck('AdminThemes')) return;

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