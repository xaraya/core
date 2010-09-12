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
 * Upgrade a theme
 *
 * @author Marty Vance
 * @param regid registered theme id
 * @returns bool
 * @return
 * @throws BAD_PARAM
 */
function themes_adminapi_upgrade($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (empty($themeInfo)) {
        xarSession::setVar('errormsg', xarML('No such theme'));
        return false;
    }

    // Update state of theme
    $res = xarMod::apiFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid, 'state' => XARTHEME_STATE_INACTIVE));

    if (!isset($res)) return;

    // Get the new version information...
    $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
    if (!isset($themeFileInfo)) return;

    // Note the changes in the database...
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

     $sql = "UPDATE $xartable[themes] SET version = ? WHERE regid = ?";
    $bindvars = array($themeFileInfo['version'],
                      $regid);

    $dbconn->Execute($sql,$bindvars);

    // Message
    xarSession::setVar('statusmsg', xarML('Theme has been upgraded, now inactive'));

    // Success
    return true;
}

?>