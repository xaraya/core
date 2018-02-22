<?php
/**
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
 */

/**
 * Remove a theme
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the id of the theme
 *        string   $args['name'] theme's name
 * @return boolean true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function themes_adminapi_remove(Array $args=array())
{
    extract($args);

    if(!xarSecurityCheck('AdminThemes')) return;

    // Remove variables and theme
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    // Get theme information
    if (isset($name)) $regid = xarMod::getRegid($name, 'theme');
    $themeInfo = xarThemeGetInfo($regid);
    $defaultTheme = xarModVars::get('themes','default_theme');

    // Bail out if we're trying to remove the default theme
    if ($defaultTheme == $themeInfo['name'] ) {
        $msg = 'The theme you are trying to remove is the current default theme. Select another default theme first, then try again.';
        throw new ForbiddenOperationException(null, $msg);
    }

    // Bail out if we're trying to remove while one of our users
    // has it set to their default theme
    $mvid = xarModVars::getId('themes','default_theme');
    $sql = "SELECT COUNT(*) FROM $tables[module_itemvars] WHERE module_var_id =? AND value = ?";
    $result =& $dbconn->Execute($sql, array($mvid,$defaultTheme));

    // count should be zero
    $count = $result->fields[0];
    if($count != 0 ) {
        $msg = 'The theme you are trying to remove is used by #(1) users on this site as their default theme. Theme cannot be removed.';
        throw new ForbiddenOperationException($count,$msg);
    }

    // Delete the theme from the themes table
    $sql = "DELETE FROM $tables[themes] WHERE regid = ?";
    $dbconn->Execute($sql,array($regid));

    return true;
}
?>
