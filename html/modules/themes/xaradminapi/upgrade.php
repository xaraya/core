<?php

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

    // Get theme database info
//    xarTheme__loadDbInfo($themeInfo['name'], $themeInfo['osdirectory']);

    // Theme upgrade function

    // pnAPI compatibility
/*    $xarinitfilename = 'themes/'. $themeInfo['osdirectory'] .'/xarinit.php';
    if (!file_exists($xarinitfilename)) {
        $xarinitfilename = 'themes/'. $themeInfo['osdirectory'] .'/pninit.php';
    }
    @include $xarinitfilename;

    $func = $themeInfo['name'] . '_upgrade';
    if (function_exists($func)) {
        if ($func($themeInfo['version']) != true) {
            return false;
        }
    }
*/

    // Update state of theme
    $res = xarModAPIFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid, 'state' => XARTHEME_STATE_INACTIVE));
    
    if (!isset($res)) return;

/*     return true; */
    // Get the new version information...
    $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
    if (!isset($themeFileInfo)) return;

    // Note the changes in the database...
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

/*     $sql = "UPDATE $xartable[themes] */
/*             SET xar_version = '" . xarVarPrepForStore($themeFileInfo['version']) . "', */
/*                 xar_admin_capable = '" . xarVarPrepForStore($themeFileInfo['admin_capable']) . "', */
/*                 xar_user_capable = '" . xarVarPrepForStore($themeFileInfo['user_capable']) . "', */
/*                 xar_class = '". xarVarPrepForStore($themeFileInfo['class']) . "', */
/*                 xar_category '". xarVarPrepForStore($themeFileInfo['category']) . "' */
/*             WHERE xar_regid = " . xarVarPrepForStore($regid); */
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