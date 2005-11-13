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
 * @raise BAD_PARAM,NO_PERMISSION
 */
function themes_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if ((!isset($regid)) ||
        (!isset($state))) {
        $msg = xarML('Empty regid (#(1)) or state (#(2)).', $regid, $state);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    // Clear cache to make sure we get newest values
    if (xarVarIsCached('Theme.Infos', $regid)) {
        xarVarDelCached('Theme.Infos', $regid);
    }

    //Get theme info
    $themeInfo = xarThemeGetInfo($regid);

    //Set up database object
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $oldState = $themeInfo['state'];

    // Check valid state transition
/*     switch ($state) { */
/*         case XARTHEME_STATE_UNINITIALISED: */
/*             // New Theme */
/*             $theme_statesTable = $xartable['system/theme_states']; */
/*             $sql = "INSERT INTO $theme_statesTable */
/*                (xar_regid, */
/*                 xar_state) */
/*                 VALUES (?,?)"; */
/*  */
/*             $result = $dbconn->Execute($sql,array($regid,$state); */
/*             if (!$result) return; */
/*  */
/*             return true; */
/*             break; */
/*         case XARTHEME_STATE_INACTIVE: */
/*             break; */
/*         case XARTHEME_STATE_ACTIVE: */
/*             if (($oldState == XARTHEME_STATE_UNINITIALISED) || */
/*                 ($oldState == XARTHEME_STATE_MISSING) || */
/*                 ($oldState == XARTHEME_STATE_UPGRADED)) { */
/*                 xarSessionSetVar('errormsg', xarML('Invalid theme state transition')); */
/*                 return false; */
/*             } */
/*             break; */
/*         case XARTHEME_STATE_MISSING: */
/*             break; */
/*         case XARTHEME_STATE_UPGRADED: */
/*             if ($oldState == XARTHEME_STATE_UNINITIALISED) { */
/*                 xarSessionSetVar('errormsg', xarML('Invalid theme state transition')); */
/*                 return false; */
/*             } */
/*             break; */
/*     } */
        switch ($state) {
        case XARTHEME_STATE_UNINITIALISED:

            if ($oldState == XARTHEME_STATE_MISSING_FROM_UNINITIALISED) break;
            if ($oldState != XARTHEME_STATE_INACTIVE) {
                // New Module
                $theme_statesTable = $xartable['system/theme_states'];
                $query = "SELECT * FROM $theme_statesTable WHERE xar_regid = ?";
                $result =& $dbconn->Execute($query,array($regid));
                if (!$result) return;
                if ($result->EOF) {
                    $query = "INSERT INTO $theme_statesTable
                       (xar_regid, xar_state)
                        VALUES (?,?)";
                    $bindvars = array($regid,$state);
                    $result =& $dbconn->Execute($query,$bindvars);
                    if (!$result) return;
                }
                return true;
            }

            break;
        case XARTHEME_STATE_INACTIVE:
            if (($oldState != XARTHEME_STATE_UNINITIALISED) &&
                ($oldState != XARTHEME_STATE_ACTIVE) &&
                ($oldState != XARTHEME_STATE_MISSING_FROM_INACTIVE) &&
                ($oldState != XARTHEME_STATE_UPGRADED)) {
                xarSessionSetVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
        case XARTHEME_STATE_ACTIVE:
            if (($oldState != XARTHEME_STATE_INACTIVE) &&
                ($oldState != XARTHEME_STATE_MISSING_FROM_ACTIVE)) {
                xarSessionSetVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
        case XARTHEME_STATE_UPGRADED:
            if (($oldState != XARTHEME_STATE_INACTIVE) &&
                ($oldState != XARTHEME_STATE_ACTIVE) &&
                $oldState != XARTHEME_STATE_MISSING_FROM_UPGRADED) {
                xarSessionSetVar('errormsg', xarML('Invalid theme state transition'));
                return false;
            }
            break;
    }
    //Get current theme mode to update the proper table
    $themeMode  = $themeInfo['mode'];

    if ($themeMode == XARTHEME_MODE_SHARED) {
        $theme_statesTable = $xartable['system/theme_states'];
    } elseif ($themeMode == XARTHEME_MODE_PER_SITE) {
        $theme_statesTable = $xartable['site/theme_states'];
    }

    $sql = "UPDATE $theme_statesTable SET xar_state = ? WHERE xar_regid =?";
    $bindvars = array($state,$regid);
    $result = $dbconn->Execute($sql, $bindvars);
    if (!$result) return;
    return true;
}

?>
