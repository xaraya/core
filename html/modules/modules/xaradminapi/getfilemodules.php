<?php

/**
 * <h1>Get module information from xarversion.php for each module </h1>
 * <br />
 * <p>Here we cycle through the modules directory and and
 * return an array of information from xarversion.php of each module.</p>
 * <br />
 * Excluded directories:
 * CVS - this is a special directory of the Concurrent Versioning System
 * SCCS - where Bitkeeper stores source files
 * PENDING - where Bitkeeper stores pending merges
 *
 * @param none
 * @returns array
 * @return an array of modules from the file system
 */
function modules_adminapi_getfilemodules()
{
    $fileModules = array();
    $dh = opendir('modules');

    while ($modOsDir = readdir($dh)) {
        if ((is_dir("modules/$modOsDir")) &&
                ($modOsDir != '.') &&
                ($modOsDir != '..') &&
                ($modOsDir != 'CVS') &&
                ($modOsDir != 'SCCS') &&
                ($modOsDir != 'PENDING')) {

            // no xarversion.php, no module
            $modFileInfo = xarMod_getFileInfo($modOsDir);
            if (!isset($modFileInfo)) {
                continue;
            }

            // Found a directory
            $name         = $modOsDir;
            $regId        = $modFileInfo['id'];
            $version      = $modFileInfo['version'];
            $mode         = XARMOD_MODE_SHARED;
            $class        = $modFileInfo['class'];
            $category     = $modFileInfo['category'];
            $adminCapable = $modFileInfo['admin_capable'];
            $userCapable  = $modFileInfo['user_capable'];
            $dependency   = $modFileInfo['dependency'];
            

            // TODO: beautify :-)
            if (!isset($regId)) {
                xarSessionSetVar('errormsg', "Module '$name' doesn't seem to have a registered module ID defined in xarversion.php - skipping...\nPlease register your module at http://www.xaraya.com");
                continue;
            }

            //Defaults
            if (!isset($version)) {
                $version = 0;
            }

            //FIXME: <johnny> add class and category checking
            if (!isset($class)) {
                $class = 'Miscellaneous';
            }

            if (!isset($category)) {
                $category = 'Miscellaneous';
            }

            // Work out if admin-capable
            if (!isset($adminCapable)) {
                $adminCapable = 0;
            }

            //FIXME: <johnny> remove this when xarversion.php contains the user setting
            if (file_exists('modules/' . $modOsDir .'/xaruser.php')) {
                $userCapable = 1;
            }

            // No dependency information = ok
            if (!isset($dependency)) {
                $dependency = array();
            }

            //FIXME: <johnny> this detection isn't finished yet... we should be checking
            //for xaruser.php and then overriding with if $modFileInfo['user_capable'] is 1
            // Work out if user-capable
            if (1 == $modFileInfo['user_capable']) {
                $userCapable = 1;
            } else {
                $userCapable = 0;
            }

            $fileModules[$name] = array('directory'     => $modOsDir,
                                        'name'          => $name,
                                        'regid'         => $regId,
                                        'version'       => $version,
                                        'mode'          => $mode,
                                        'class'         => $class,
                                        'category'      => $category,
                                        'admin_capable' => $adminCapable,
                                        'user_capable'  => $userCapable,
                                        'dependency'    => $dependency);
        }
    }
    closedir($dh);

    return $fileModules;
}

?>
