<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 */

/**
 * Get module information from xarversion.php for each module
 *
 * Here we cycle through the modules directory and and
 * return an array of information from xarversion.php of each module.
 *
 * Excluded directories:
 * MT  - this is a special directory of Monotone
 * CVS - this is a special directory of the Concurrent Versioning System
 * SCCS - where Bitkeeper stores source files
 * PENDING - where Bitkeeper stores pending merges
 *
 * @param $args['regid'] - optional regid to retrieve
 * @return array modules from the file system
 */
function modules_adminapi_getfilemodules($args)
{
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $fileModules = array();
    $dh = opendir('modules');

    while ($modOsDir = readdir($dh)) {
        switch ($modOsDir) {
            case '.':
            case '..':
            case 'MT':
            case 'CVS':
            case 'SCCS':
            case 'PENDING':
            case 'notinstalled':
                break;
            default:
                if (is_dir("modules/$modOsDir")) {

                    // no xarversion.php, no module
                    $modFileInfo = xarMod_getFileInfo($modOsDir);
                    if (!isset($modFileInfo)) {
                        continue;
                    }

                    // Found a directory
                    $name         = $modOsDir;
                    $nameinfile   = $modFileInfo['name'];
                    $regId        = $modFileInfo['id'];
                    $version      = $modFileInfo['version'];
                    $class        = $modFileInfo['class'];
                    $category     = $modFileInfo['category'];
                    $adminCapable = $modFileInfo['admin_capable'];
                    $userCapable  = $modFileInfo['user_capable'];
                    $dependency   = $modFileInfo['dependency'];

                    // TODO: beautify :-)
                    if (!isset($regId)) {
                        xarSession::setVar('errormsg', "Module '$name' doesn't seem to have a registered module ID defined in xarversion.php - skipping...\nPlease register your module at http://www.xaraya.com");
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

                    //Check for duplicates
                    foreach ($fileModules as $module) {
                        if($regId == $module['regid']) {
                            $msg = 'The same registered ID (#(1)) was found in two different modules, #(2) and #(3). Please remove one of the modules and regenerate the list.';
                            $vars = array($regId, $name, $module['name']);
                            throw new DuplicateException($vars,$msg);
                        }
                        if($nameinfile == $module['nameinfile']) {
                            $msg = 'The module #(1) was found under two different registered IDs, #(2) and #(3). Please remove one of the modules and regenerate the list';
                            $vars = array($nameinfile, $regId, $module['regid']);
                            throw new DuplicateException($vars,$msg);
                        }
                    }
                    if ($modregid == $regId) {
                            closedir($dh);
                            // Just return array without module name index
                            return array('directory'     => $modOsDir,
                                         'name'          => $name,
                                         'nameinfile'    => $nameinfile,
                                         'regid'         => $regId,
                                         'version'       => $version,
                                         'class'         => $class,
                                         'category'      => $category,
                                         'admin_capable' => $adminCapable,
                                         'user_capable'  => $userCapable,
                                         'dependency'    => $dependency);
                    } else {
                            $fileModules[$name] = array('directory'     => $modOsDir,
                                                        'name'          => $name,
                                                        'nameinfile'    => $nameinfile,
                                                        'regid'         => $regId,
                                                        'version'       => $version,
                                                        'class'         => $class,
                                                        'category'      => $category,
                                                        'admin_capable' => $adminCapable,
                                                        'user_capable'  => $userCapable,
                                                        'dependency'    => $dependency);
                    } // if
                } // if
        } // switch
    } // while
    closedir($dh);

    return $fileModules;
}
?>
