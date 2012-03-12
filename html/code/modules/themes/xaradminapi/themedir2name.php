<?php
/**
 * Convert a theme directory to a theme name.
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

/**
 * Convert a theme directory to a theme name.
 *
 * @author Roger Keays <r.keays@ninthave.net>
 * @param array    $args array of optional parameters<br/>
 *        string   $args['directory'] of the theme
 * @return  string the theme name in this directory, or false if theme is not
 *          found
 */
function themes_adminapi_themedir2name(Array $args=array())
{
    $allthemes = xarMod::apiFunc('themes', 'admin', 'getfilethemes');
    foreach ($allthemes as $theme) {
        if ($theme['directory'] == $args['directory']) {
            return $theme['name'];
        }
    }
    return false;
}
?>
