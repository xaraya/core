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
        }
    }

    return true;
}

?>
