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
 * Set the state of a theme
 *
 * @author Marty Vance
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] the theme id<br/>
 *        string   $args['name'] themes's name
 *        integer  $args['state'] the state
 * @throws BAD_PARAM,NO_PERMISSION
 */
function themes_adminapi_setstate(Array $args=array())
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (isset($name)) $regid = xarMod::getRegid($name, 'theme');
    if (!isset($regid)) throw new EmptyParameterException('regid');
    if (!isset($state)) throw new EmptyParameterException('state');

    // Security Check
    if(!xarSecurityCheck('AdminThemes')) return;

    // Clear cache to make sure we get newest values
    if (xarVarIsCached('Theme.Infos', $regid)) {
        xarVarDelCached('Theme.Infos', $regid);
    }

    //Get theme info
    $themeInfo = xarThemeGetInfo($regid);

    //Set up database object
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $themesTable = $xartable['themes'];

    $oldState = $themeInfo['state'];

    switch ($state) {
    case XARTHEME_STATE_UNINITIALISED:
        // Are we always good here?
        if ($oldState == XARTHEME_STATE_MISSING_FROM_UNINITIALISED) break;
        if ($oldState != XARTHEME_STATE_INACTIVE)         break;
        break;
    case XARTHEME_STATE_INACTIVE:
        if (($oldState != XARTHEME_STATE_UNINITIALISED) &&
            ($oldState != XARTHEME_STATE_ACTIVE) &&
            ($oldState != XARTHEME_STATE_MISSING_FROM_INACTIVE) &&
            ($oldState != XARTHEME_STATE_UPGRADED)) {
            xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
            return false;
        }
        break;
    case XARTHEME_STATE_ACTIVE:
        if (($oldState != XARTHEME_STATE_INACTIVE) &&
            ($oldState != XARTHEME_STATE_MISSING_FROM_ACTIVE)) {
            xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
            return false;
        }
        break;
    case XARTHEME_STATE_UPGRADED:
        if (($oldState != XARTHEME_STATE_INACTIVE) &&
            ($oldState != XARTHEME_STATE_ACTIVE) &&
            $oldState != XARTHEME_STATE_MISSING_FROM_UPGRADED) {
            xarSession::setVar('errormsg', xarML('Invalid theme state transition'));
            return false;
        }
        break;
    }
    // If we end up here, things are good
    $query = "UPDATE $themesTable SET state = ? WHERE regid = ? ";
    $dbconn->Execute($query,array($state,$regid));
    return true;
}

?>