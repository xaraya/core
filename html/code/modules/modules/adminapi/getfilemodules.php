<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminApi;
use xarController;
use xarMod;
use xarSession;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi getfilemodules function
 * @extends MethodClass<AdminApi>
 */
class GetfilemodulesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get module information from xarversion.php for each module
     * Here we cycle through the modules directory and and
     * return an array of information from xarversion.php of each module.
     *
     * Excluded directories:
     * MT  - this is a special directory of Monotone
     * CVS - this is a special directory of the Concurrent Versioning System
     * SCCS - where Bitkeeper stores source files
     * PENDING - where Bitkeeper stores pending merges
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] - optional regid to retrieve
     * @return array|bool modules from the file system
     * @see AdminApi::getfilemodules()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments
        extract($args);

        // Check for $regId
        $modregid = 0;
        if (isset($regId)) {
            $modregid = $regId;
        }

        $fileModules = [];
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
                        $modFileInfo = $this->mod()->getFileInfo($modOsDir);
                        if (empty($modFileInfo)) {
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
                            $this->session()->setVar('errormsg', "Module '$name' doesn't seem to have a registered module ID defined in xarversion.php - skipping...\nPlease register your module at http://www.xaraya.com");
                            continue 2;
                        }

                        //Check for duplicates
                        foreach ($fileModules as $module) {
                            if ($regId == $module['regid']) {
                                $msg = $this->ml('The same registered ID (#(1)) was found in two different modules, #(2) and #(3). Please remove one of the modules and regenerate the list.', $regId, $name, $module['name']);
                                $this->ctl()->redirect($this->ctl()->getModuleURL(
                                    'modules',
                                    'user',
                                    'errors',
                                    ['message' => urlencode($msg)]
                                ));
                                return true;
                            }
                            if ($nameinfile == $module['nameinfile']) {
                                $msg = $this->ml('The module #(1) was found under two different registered IDs, #(2) and #(3). Please remove one of the modules and regenerate the list', $nameinfile, $regId, $module['regid']);
                                $this->ctl()->redirect($this->ctl()->getModuleURL(
                                    'modules',
                                    'user',
                                    'errors',
                                    ['message' => urlencode($msg)]
                                ));
                                return true;
                            }
                        }
                        if ($modregid == $regId) {
                            closedir($dh);
                            // Just return array without module name index
                            return ['directory'     => $modOsDir,
                                'name'          => $name,
                                'nameinfile'    => $nameinfile,
                                'regid'         => $regId,
                                'version'       => $version,
                                'class'         => $class,
                                'category'      => $category,
                                'admin_capable' => $adminCapable,
                                'user_capable'  => $userCapable,
                                'dependency'    => $dependency,
                                'dependencyinfo' => $dependencyinfo,
                                'namespace'     => $namespace,
                                'twigtemplates' => $twigtemplates,
                                'twigextension' => $twigextension];
                        } else {
                            $fileModules[$name] = ['directory'     => $modOsDir,
                                'name'          => $name,
                                'nameinfile'    => $nameinfile,
                                'regid'         => $regId,
                                'version'       => $version,
                                'class'         => $class,
                                'category'      => $category,
                                'admin_capable' => $adminCapable,
                                'user_capable'  => $userCapable,
                                'dependency'    => $dependency,
                                'dependencyinfo' => $dependencyinfo,
                                'namespace'     => $namespace,
                                'twigtemplates' => $twigtemplates,
                                'twigextension' => $twigextension];
                        } // if
                    } // if
            } // switch
        } // while
        closedir($dh);

        return $fileModules;
    }
}
