<?php
/**
 * File: $Id$
 *
 * Upgrade a theme
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
 * Upgrade a theme
 *
 * @param regid registered theme id
 * @returns bool
 * @return
 * @raise BAD_PARAM
 */
function themes_adminapi_upgrade($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (empty($themeInfo)) {
        xarSessionSetVar('errormsg', xarML('No such theme'));
        return false;
    }

    // Update state of theme
    $res = xarModAPIFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid, 'state' => XARTHEME_STATE_INACTIVE));
    
    if (!isset($res)) return;

    // Get the new version information...
    $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
    if (!isset($themeFileInfo)) return;

    // Note the changes in the database...
    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();

     $sql = "UPDATE $xartable[themes]
            SET xar_version = '" . xarVarPrepForStore($themeFileInfo['version']) . "',
                xar_class = ". xarVarPrepForStore($themeFileInfo['class']) . " 
            WHERE xar_regid = " . xarVarPrepForStore($regid);        
            
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    // Message
    xarSessionSetVar('statusmsg', xarML('Theme has been upgraded, now inactive'));

    // Success
    return true;
}

?>
