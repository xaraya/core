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
use EmptyParameterException;
use Exception;
use ModuleNotFoundException;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi executeinitfunction function
 * @extends MethodClass<AdminApi>
 */
class ExecuteinitfunctionMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Loads xarinit.php file or module installer class and executes the given function
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id of the module<br/>
     * string   $args['function'] name of the function to be called
     * @return bool|void true on success, false on failure in the called function
     * @throws \EmptyParameterException
     * @see AdminApi::executeinitfunction()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Argument check
        if (!isset($args['regid'])) {
            throw new EmptyParameterException('regid');
        }

        // Get module information
        $modInfo = $this->mod()->getInfo($args['regid']);

        if (!isset($modInfo['osdirectory'])
            || empty($modInfo['osdirectory'])
            || !is_dir(sys::code() . 'modules/' . $modInfo['osdirectory'])) {

            $msg = 'Module (regid: #(1) - directory: #(2) does not exist.';
            $vars = [$args['regid'], $modInfo['osdirectory']];
            throw new ModuleNotFoundException($vars, $msg);
        }

        // Get module database info, they might be needed in the function to be called
        $this->mod()->loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

        $xarinitfile = '';
        if (file_exists(sys::code() . 'modules/' . $modInfo['osdirectory'] . '/xarinit.php')) {
            $xarinitfile = sys::code() . 'modules/' . $modInfo['osdirectory'] . '/xarinit.php';
        } else {
            // use modType = 'installer' here to get the module Installer class (if available)
            $func = $this->mod()->getModuleClassMethod($modInfo['name'], 'installer', $args['function'], 'api');
            if (!empty($func)) {
                $this->run_callable($func, $args, $modInfo);
                return true;
            }
        }
        // If there is no xarinit file, there is apparently nothing to init.
        // TODO: we migh consider making it required.
        if (empty($xarinitfile)) {
            return true;
        }


        // if (!empty($xarinitfile)) {
        ob_start();
        $r = sys::import('modules.' . $modInfo['osdirectory'] . '.xarinit');
        $error_msg = strip_tags(ob_get_contents());
        ob_end_clean();

        if (empty($r) || !$r) {
            $msg = $this->ml("Could not load file: [#(1)].\n\n Error Caught:\n #(2)", $xarinitfile, $error_msg);
            throw new Exception($msg);
        }

        $func = $modInfo['name'] . '_' . $args['function'];
        if (!empty($modInfo['namespace']) && !function_exists($func)) {
            $func = $modInfo['namespace'] . '\\' . $func;
        }
        if (function_exists($func)) {
            $this->run_callable($func, $args, $modInfo);
        }
        return true;
    }

    public function run_callable($func, $args, $modInfo)
    {
        if ($args['function'] == 'upgrade') {
            // pass the old version as argument to the upgrade function
            $result = $func($modInfo['version']);
        } else {
            $result = $func();
        }

        if ($result === false) {
            $msg = $this->ml('While changing state of the #(1) module, the function #(2) returned a false value when executed.', $modInfo['name'], $func);
            throw new Exception($msg);
        } elseif ($result != true) {
            $msg = $this->ml('An error ocurred while changing state of the #(1) module, executing function #(2)', $modInfo['name'], $func);
            throw new Exception($msg);
        }
    }
}
