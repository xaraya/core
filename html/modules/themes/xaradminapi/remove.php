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
 * @throws BAD_PARAM, NO_PERMISSION
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

    // count should be zero
    $count = $result->fields[0];
    if($count != 0 ) {
        $msg = 'The theme you are trying to remove is used by #(1) users on this site as their default theme. Theme cannot be removed.';
        throw new ForbiddenOperationException($count,$msg);
    }        
    
    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    // Delete the theme from the themes table
    $sql = "DELETE FROM $tables[themes] WHERE xar_regid = ?";
    $dbconn->Execute($sql,array($regid));
    return true;
}

?>
