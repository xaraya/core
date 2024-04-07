<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
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
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        integer  $args['regid'] - optional regid to retrieve
 * @return array<mixed>|bool modules from the file system
 */
function modules_adminapi_getfilemodules(array $args = [], $context = null)
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
                    $modFileInfo = xarMod::getFileInfo($modOsDir);
                    if (!isset($modFileInfo)) {
                        continue 2;
                    }

                    // Found a directory
                    $name           = $modOsDir;
                    $nameinfile     = $modFileInfo['name'];
                    $regId          = $modFileInfo['regid'];
                    $version        = $modFileInfo['version'];
                    $class          = $modFileInfo['class'];
                    $category       = $modFileInfo['category'];
                    $adminCapable   = $modFileInfo['admin_capable'];
                    $userCapable    = $modFileInfo['user_capable'];
                    $dependency     = $modFileInfo['dependency'];
                    $dependencyinfo = $modFileInfo['dependencyinfo'];
                    $namespace      = $modFileInfo['namespace'] ?? '';
                    $twigtemplates  = $modFileInfo['twigtemplates'] ?? false;
                    $twigextension  = $modFileInfo['twigextension'] ?? '.html.twig';
            
                    // TODO: beautify :-)
                    if (!isset($regId)) {
                        xarSession::setVar('errormsg', "Module '$name' doesn't seem to have a registered module ID defined in xarversion.php - skipping...\nPlease register your module at http://www.xaraya.com");
                        continue 2;
                    }

                    //Check for duplicates
                    foreach ($fileModules as $module) {
                        if($regId == $module['regid']) {
                            $msg = xarML('The same registered ID (#(1)) was found in two different modules, #(2) and #(3). Please remove one of the modules and regenerate the list.',$regId, $name, $module['name']);
                            xarController::redirect(xarController::URL('modules', 'user', 'errors',
                                array('message' => urlencode($msg))), null, $context);
                            return true;
                        }
                        if($nameinfile == $module['nameinfile']) {
                            $msg = xarML('The module #(1) was found under two different registered IDs, #(2) and #(3). Please remove one of the modules and regenerate the list',$nameinfile, $regId, $module['regid']);
                            xarController::redirect(xarController::URL('modules', 'user', 'errors',
                                array('message' => urlencode($msg))), null, $context);
                            return true;
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
                                         'dependencyinfo'=> $dependencyinfo,
                                         'namespace'     => $namespace,
                                         'twigtemplates' => $twigtemplates,
                                         'twigextension' => $twigextension);
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
                                                        'dependencyinfo'=> $dependencyinfo,
                                                        'namespace'     => $namespace,
                                                        'twigtemplates' => $twigtemplates,
                                                        'twigextension' => $twigextension);
                    } // if
                } // if
        } // switch
    } // while
    closedir($dh);

    return $fileModules;
}
