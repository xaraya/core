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
 * Remove a theme
 *
 * @author Marty Vance
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
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    $defaultTheme = xarModGetVar('themes','default');

    // Bail out if we're trying to remove the default theme
    if ($defaultTheme == $themeInfo['name'] ) {
        $msg = 'The theme you are trying to remove is the current default theme. Select another default theme first, then try again.';
        throw new ForbiddenOperationException(null, $msg);
    }
    
    // Bail out if we're trying to remove while one of our users
    // has it set to their default theme
    $mvid = xarModGetVarId('themes','default');
    $sql = "SELECT COUNT(*) FROM $tables[module_itemvars] WHERE xar_mvid=? AND xar_value = ?";
    $result =& $dbconn->Execute($sql, array($mvid,$defaultTheme));
    if(!$result) return;
    // count should be zero
    $count = $result->fields[0];
    if($count != 0 ) {
        $msg = 'The theme you are trying to remove is used by #(1) users on this site as their default theme. Theme cannot be removed.';
        throw new ForbiddenOperationException($count,$msg);
    }        
    
    
    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    // Delete any theme variables that the theme cleanup function might
    // have missed
    $sql = "DELETE FROM $tables[theme_vars] WHERE xar_themeName = ?";
    $result = $dbconn->Execute($sql,array($themeInfo['name']));
    if (!$result) return;

    // Delete the theme from the themes table
    $sql = "DELETE FROM $tables[themes] WHERE xar_regid = ?";
    $result = $dbconn->Execute($sql,array($regid));
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
    $sql = "DELETE FROM $theme_statesTable  WHERE xar_regid = ?";

    $result = $dbconn->Execute($sql,array($regid));
    if (!$result) return;

    return true;
}

?>
