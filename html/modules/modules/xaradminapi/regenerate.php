<?php

/**
 * Regenerate module list
 *
 * @param none
 * @returns bool
 * @return true on success, false on failure
 * @raise NO_PERMISSION
 */
function modules_adminapi_regenerate()
{
    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    //Finds and updates missing modules
    if (!xarModAPIFunc('modules','admin','checkmissing')) {return;}

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    //Setup database object for module insertion
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    // See if we have gained any modules since last generation,
    // or if any current modules have been upgraded
    foreach ($fileModules as $name => $modinfo) {
        foreach ($dbModules as $dbmodule) {
            if(($modinfo['regid'] == $dbmodule['regid']) && ($modinfo['name'] != $dbmodule['name'])) {
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) module in the file system and a registered #(3) module in the database. Please correct this and regenerate the list.', $dbmodule['regid'], $modinfo['name'], $dbmodule['name']);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            if(($modinfo['name'] == $dbmodule['name']) && ($modinfo['regid'] != $dbmodule['regid'])) {
                $msg = xarML('The module #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $modinfo['name'], $modinfo['regid'], $dbmodule['regid']);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
        }
        if (empty($dbModules[$name])) {

            // New module
            $modId = $dbconn->GenId($xartable['modules']);
            $sql = "INSERT INTO $xartable[modules]
                      (xar_id,
                       xar_name,
                       xar_regid,
                       xar_directory,
                       xar_version,
                       xar_mode,
                       xar_class,
                       xar_category,
                       xar_admin_capable,
                       xar_user_capable)
                    VALUES
                      (" . xarVarPrepForStore($modId) . ",
                       '" . xarVarPrepForStore($modinfo['name']) . "',
                       '" . xarVarPrepForStore($modinfo['regid']) . "',
                       '" . xarVarPrepForStore($modinfo['directory']) . "',
                       '" . xarVarPrepForStore($modinfo['version']) . "',
                       '" . xarVarPrepForStore($modinfo['mode']) . "',
                       '" . xarVarPrepForStore($modinfo['class']) . "',
                       '" . xarVarPrepForStore($modinfo['category']) . "',
                       '" . xarVarPrepForStore($modinfo['admin_capable']) . "',
                       '" . xarVarPrepForStore($modinfo['user_capable']) . "')";
            $result =& $dbconn->Execute($sql);

            if (!$result) return;

            $set = xarModAPIFunc('modules',
                                'admin',
                                'setstate',
                                array('regid' => $modinfo['regid'],
                                      'state' => XARMOD_STATE_UNINITIALISED));
            if (!isset($set)) return;

        } else {
          // BEGIN bugfix (561802) - cmgrote
            if ($dbModules[$name]['version'] != $modinfo['version'] && $dbModules[$name]['state'] != XARMOD_STATE_UNINITIALISED) {
                    $set = xarModAPIFunc('modules',
                                        'admin',
                                        'setstate',
                                        array('regid' => $dbModules[$name]['regid'],
                                              'state' => XARMOD_STATE_UPGRADED));
                    if (!isset($set)) die('upgrade');
                }

            if ($dbModules[$name]['state'] == XARMOD_STATE_MISSING) {
//            echo $name." " . $dbModules[$name]['state']."<br />";
            $set = xarModAPIFunc('modules',
                                'admin',
                                'setstate',
                                 array('regid' => $dbModules[$name]['regid'],
                                       'state' => XARMOD_STATE_UNINITIALISED));
            }
            //remove the db entry with a corresponding module in the file system
            unset($dbModules[$name]);
        }
    }

    // Now go through the remaining entries in the db that we didn't meet in the file system
    // They must be flagged as missing
        foreach ($dbModules as $module) {
            $set = xarModAPIFunc('modules',
                                'admin',
                                'setstate',
                                 array('regid' => $module['regid'],
                                       'state' => XARMOD_STATE_MISSING));
        }

    return true;
}

?>
