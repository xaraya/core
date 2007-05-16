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
 * Set the state of a theme
 *
 * @author Marty Vance
 * @param $args['regid'] the theme id
 * @param $args['state'] the state
 * @throws BAD_PARAM,NO_PERMISSION
 */
function themes_adminapi_setstate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    if (!isset($state)) throw new EmptyParameterException('state');

    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    // Clear cache to make sure we get newest values
    if (xarVarIsCached('Theme.Infos', $regid)) {
        xarVarDelCached('Theme.Infos', $regid);
    }

    //Get theme info
    $themeInfo = xarThemeGetInfo($regid);

    //Set up database object
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();
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
