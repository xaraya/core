<?php
/**
 * Convert a theme directory to a theme name.
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes module
 */

/**
 * Convert a theme directory to a theme name.
 *
 * @author Roger Keays <r.keays@ninthave.net>
 * @param   directory of the theme
 * @return  the theme name in this directory, or false if theme is not
 *          found
 */
function themes_adminapi_themedir2name($args)
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
