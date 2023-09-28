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
 * Upgrade a theme
 *
 * @author Marty Vance
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['regid'] registered theme id
 * @return boolean|void true on success, false on failure
 * @throws EmptyParameterException
 */
function themes_adminapi_upgrade(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Get theme information
    $themeInfo = xarTheme::getInfo($regid);
    if (empty($themeInfo)) {
        xarSession::setVar('errormsg', xarML('No such theme'));
        return false;
    }

    // Update state of theme
    $res = xarMod::apiFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid, 'state' => xarTheme::STATE_INACTIVE));

    if (!isset($res)) return;

    // Get the new version information...
    $themeFileInfo = xarTheme::getFileInfo($themeInfo['osdirectory']);
    if (!isset($themeFileInfo)) return;

    // Note the changes in the database...
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

     $sql = "UPDATE $xartable[themes] SET version = ? WHERE regid = ?";
    $bindvars = array($themeFileInfo['version'],
                      $regid);

    $dbconn->Execute($sql,$bindvars);

    // Message
    xarSession::setVar('statusmsg', xarML('Theme has been upgraded, now inactive'));

    // Success
    return true;
}
