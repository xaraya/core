<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
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
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['regid'] - optional regid to retrieve
 * @return array modules from the file system
 */
function modules_adminapi_getfilemodules(Array $args=array())
{
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $fileModules = array();
    $dh = opendir(sys::code() . 'modules');

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
                if (is_dir(sys::code() . "modules/$modOsDir")) {

                    // no xarversion.php, no module
                    $modFileInfo = xarMod_getFileInfo($modOsDir);
                    if (!isset($modFileInfo)) {
                        continue;
                    }

                    // Found a directory
                    $name           = $modOsDir;
                    $nameinfile     = $modFileInfo['name'];
                    $regId          = $modFileInfo['id'];
                    $version        = $modFileInfo['version'];
                    $class          = $modFileInfo['class'];
                    $category       = $modFileInfo['category'];
                    $adminCapable   = $modFileInfo['admin_capable'];
                    $userCapable    = $modFileInfo['user_capable'];
                    $dependency     = $modFileInfo['dependency'];
                    $dependencyinfo = $modFileInfo['dependencyinfo'];

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
                        $adminCapable = false;
                    }

                    //FIXME: <johnny> remove this when xarversion.php contains the user setting
                    if (file_exists(sys::code() . 'modules/' . $modOsDir .'/xaruser.php')) {
                        $userCapable = true;
                    }

                    // No dependency information = ok
                    if (!isset($dependency)) {
                        $dependency = array();
                    }

                    //FIXME: <johnny> this detection isn't finished yet... we should be checking
                    //for xaruser.php and then overriding with if $modFileInfo['user_capable'] is 1
                    // Work out if user-capable
                    if (true == $modFileInfo['user_capable']) {
                        $userCapable = true;
                    } else {
                        $userCapable = false;
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
                                         'dependency'    => $dependency,
                                         'dependencyinfo'=> $dependencyinfo);
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
                                                        'dependency'    => $dependency,
                                                        'dependencyinfo'=> $dependencyinfo);
                    } // if
                } // if
        } // switch
    } // while
    closedir($dh);

    return $fileModules;
}
?>
