<?php
/**
 * File: $Id$
 *
 * Regenerate module list
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
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
    $dbconn =& xarDBGetConn(0);
    $xartable =& xarDBGetTables();
    $modules_table = $xartable['modules'];

    // See if we have gained any modules since last generation,
    // or if any current modules have been upgraded
    foreach ($fileModules as $name => $modinfo) {

        // Check matching name and regid values
        foreach ($dbModules as $dbmodule) {
            // Bail if 2 modules have the same regid but not the same name
            if(($modinfo['regid'] == $dbmodule['regid']) && 
               ($modinfo['name'] != $dbmodule['name'])) {
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) module in the file system and a registered #(3) module in the database. Please correct this and regenerate the list.', $dbmodule['regid'], $modinfo['name'], $dbmodule['name']);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }

            // Bail if 2 modules have the same name but not the same regid
            if(($modinfo['name'] == $dbmodule['name']) && 
               ($modinfo['regid'] != $dbmodule['regid'])) {
                $msg = xarML('The module #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $modinfo['name'], $modinfo['regid'], $dbmodule['regid']);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
        }

        // If this is a new module, i.e. not in the db list, add it
        if (empty($dbModules[$name])) {
            // New module
            $modId = $dbconn->GenId($modules_table);
            $sql = "INSERT INTO $modules_table
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
            // Bug 1664 - If the module version changes for a module that has NOT been
            // installed, then simply update the database with the new version number
            // without admin intervention rather than try to upgrade.
            if ($dbModules[$name]['version'] != $modinfo['version']) {
                // Check if database version is less than file version 
                if ($dbModules[$name]['version'] < $modinfo['version']) {
                    // Automatically update the module version for uninstalled modules
                    if ($dbModules[$name]['state'] == XARMOD_STATE_UNINITIALISED ||
                        $dbModules[$name]['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                        $dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED)
                    {
                        // Update the module version number
                        $sql = "UPDATE $modules_table
                                SET xar_version = '" . xarVarPrepForStore($modinfo['version']) . "'
                                WHERE xar_regid = " . xarVarPrepForStore($modinfo['regid']);

                        $result = $dbconn->Execute($sql);
                        if (!$result) return;
                    } else {
                        // Else set the module state to upgraded
                        $set = xarModAPIFunc('modules',
                                             'admin',
                                             'setstate', 
                                             array('regid' =>   $modinfo['regid'],
                                                   'state' => XARMOD_STATE_UPGRADED));

                        if (!isset($set)) return;
                    }
                } else {
                    // The database version is greater than the file version.
                    // We can't deactivate or remove the module as the user will 
                    // lose all of their data, so the module should be placed into
                    // a holding state until the user has updated the files for
                    // the module and the module version is the same or greater
                    // than the db version.

                    // Check if error state is already set
                    if (($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED) ||
                        ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_INACTIVE) ||
                        ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_ACTIVE) ||
                        ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UPGRADED)) {
                        // Continue to next module
                        continue;
                    }

                    // Clear cache to make sure we set the correct states
                    //if (xarVarIsCached('Mod.Infos', $modinfo['regid'])) {
                    //    xarVarDelCached('Mod.Infos', $modinfo['regid']);
                    //}

                    // Set error state
                    $modstate = XARMOD_STATE_ANY;
                    switch ($dbModules[$name]['state']) {
                        case XARMOD_STATE_UNINITIALISED:
                            $modstate = XARMOD_STATE_ERROR_UNINITIALISED;
                            break;
                        case XARMOD_STATE_INACTIVE:
                            $modstate = XARMOD_STATE_ERROR_INACTIVE;
                            break;
                        case XARMOD_STATE_ACTIVE:
                            $modstate = XARMOD_STATE_ERROR_ACTIVE;
                            break;
                        case XARMOD_STATE_UPGRADED:
                            $modstate = XARMOD_STATE_ERROR_UPGRADED;
                            break;
                    }
                    if ($modstate != XARMOD_STATE_ANY) {
                        $set = xarModAPIFunc('modules', 
                                             'admin', 
                                             'setstate',
                                             array( 'regid' => $dbModules[$name]['regid'],
                                                    'state' => $modstate));
                        if (!isset($set)) return;

                        // Continue to next module
                        continue;
                    }
                }
            }

            // From here on we have something in the file system or the db
            $newstate = XARMOD_STATE_ANY;
            switch ($dbModules[$name]['state']) {
                case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
                case XARMOD_STATE_ERROR_UNINITIALISED:
                    $newstate = XARMOD_STATE_UNINITIALISED;
                    break;
                case XARMOD_STATE_MISSING_FROM_INACTIVE:
                case XARMOD_STATE_ERROR_INACTIVE:
                    $newstate = XARMOD_STATE_INACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_ACTIVE:
                case XARMOD_STATE_ERROR_ACTIVE:
                    $newstate = XARMOD_STATE_ACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_UPGRADED:
                case XARMOD_STATE_ERROR_UPGRADED:
                    $newstate = XARMOD_STATE_UPGRADED;
                    break;
            }
            if ($newstate != XARMOD_STATE_ANY) {
                $set = xarModAPIFunc('modules', 
                                     'admin', 
                                     'setstate',
                                     array( 'regid' => $dbModules[$name]['regid'],
                                            'state' => $newstate));
            }
            // Check if there was a version change and adjust
            xarModAPIFunc('modules','admin','checkversion');
        }
    }

    return true;
}

?>
