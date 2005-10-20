<?php
/**
 * File: $Id$
 *
 * @package themes
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Roger Keays <r.keays@ninthave.net>
 */


/**
 * Convert a theme directory to a theme name.
 *
 * @param   directory of the theme
 * @return  the theme name in this directory, or false if theme is not
 *          found
 */
function themes_adminapi_themedir2name($args)
{
    $allthemes = xarModAPIFunc('themes', 'admin', 'getfilethemes');
    foreach ($allthemes as $theme) {
        if ($theme['directory'] == $args['directory']) {
            return $theme['name'];
        }
    }
    return false;
}
?>
