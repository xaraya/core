<?php
/**
 * File: $Id$
 *
 * Remove a theme
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage Themes
 * @author Marty Vance
*/
/**
 * Remove a theme
 *
 * @param $args['regid'] the id of the theme
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function themes_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
	if(!xarSecurityCheck('AdminTheme')) return;

    // Remove variables and theme
    list($dbconn) = xarDBGetConn();
    $tables =& xarDBGetTables();

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);

    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    // Delete any theme variables that the theme cleanup function might
    // have missed
    $sql = "DELETE FROM $tables[theme_vars]
            WHERE xar_themeName = '" . xarVarPrepForStore($themeInfo['name']) . "'";

    $result = $dbconn->Execute($sql);
    if (!$result) return;

    // Delete the theme from the themes table
    $sql = "DELETE FROM $tables[themes]
            WHERE xar_regid = " . xarVarPrepForStore($regid);
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    // Delete the theme state from the theme states table

    //Get current theme mode to update the proper table
    $themeMode  = $themeInfo['mode'];

    /*
    if ($themeMode == XARTHEME_MODE_SHARED) {
        $theme_statesTable = $tables['system/theme_states'];
    } elseif ($themeMode == XARTHEME_MODE_PER_SITE) {
        $theme_statesTable = $tables['site/theme_states'];
    }

    // TODO: what happens if a theme state is still there in one of the subsites ?
    //    $theme_statesTable = $tables['site/theme_states'];
    */

    $theme_statesTable = $tables['site/theme_states'];
    $sql = "DELETE FROM $theme_statesTable
            WHERE xar_regid = " . xarVarPrepForStore($regid);

    $result = $dbconn->Execute($sql);
    if (!$result) return;

    return true;
}

?>
