<?php

/**
 * Upgrade a module
 *
 * @param regid registered module id
 * @returns bool
 * @return
 * @raise BAD_PARAM
 */
function modules_adminapi_upgrade($args)
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

    // Get module information
    $modInfo = xarModGetInfo($regid);
    if (empty($modInfo)) {
        xarSessionSetVar('errormsg', xarML('No such module'));
        return false;
    }

    // Get module database info
    xarMod__loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    // Module upgrade function

    // pnAPI compatibility
    $xarinitfilename = 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    if (!file_exists($xarinitfilename)) {
        $xarinitfilename = 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }
    @include $xarinitfilename;

    $func = $modInfo['name'] . '_upgrade';
    if (function_exists($func)) {
        if ($func($modInfo['version']) != true) {
            return false;
        }
    }

    // Update state of module
    $res = xarModAPIFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_INACTIVE));
    if (!isset($res)) return;


    // Get the new version information...
    $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
    if (!isset($modFileInfo)) return;

    // Note the changes in the database...
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $sql = "UPDATE $xartable[modules]
            SET xar_version = '" . xarVarPrepForStore($modFileInfo['version']) . "',
                xar_admin_capable = '" . xarVarPrepForStore($modFileInfo['admin_capable']) . "',
                xar_user_capable = '" . xarVarPrepForStore($modFileInfo['user_capable']) . "',
                xar_class = '". xarVarPrepForStore($modFileInfo['class']) . "',
                xar_category = '". xarVarPrepForStore($modFileInfo['category']) . "'
            WHERE xar_regid = " . xarVarPrepForStore($regid);
    $result = $dbconn->Execute($sql);
    if (!$result) return;

    // Message
    xarSessionSetVar('statusmsg', xarML('Module has been upgraded, now inactive'));

    // Success
    return true;
}

?>
