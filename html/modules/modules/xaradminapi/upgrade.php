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

    // Module deletion function
    if (!xarModAPIFunc('modules',
                       'admin',
                       'executeinitfunction',
                       array('regid'    => $regid,
                             'function' => 'init'))) {
        //Raise an Exception
        return;
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
