<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer;

use Xaraya\Modules\AdminApiClass;
use BadParameterException;
use EmptyParameterException;
use Exception;
use FileNotFoundException;
use FunctionNotFoundException;
use sys;

sys::import('xaraya.modules.adminapi');

/**
 * Handle the installer admin API
 * @extends AdminApiClass<Module>
 */
class AdminApi extends AdminApiClass
{
    public function configure()
    {
        $this->setModType('admin');
        // don't call xar::mod()->apiLoad() for xarInstall::apiFunc()
    }

    /**
     * Modify one or more variables in a configuration file
     *
     * @author Marc Lutolf
     * @param array<string, mixed> $args array of optional parameters<br/>
     * @param string args['variables'] = array($name => $value,...)
     * @return boolean
     */
    public function modifysystemvars(array $args = [])
    {
        // We need variables to save
        if (!isset($args['variables'])) {
            throw new BadParameterException('variables');
        }

        // Get the path to the file we are updating
        if (!isset($args['scope'])) {
            $args['scope'] = 'System';
        }
        if ($args['scope'] == 'System') {
            $configfile = sys::varpath() . '/config.system.php';
        } elseif ($args['scope'] == 'Log') {
            $configfile = sys::varpath() . '/logs/config.log.php';
        } else {
            throw new Exception(xarML("xarSystemVars: Unknown scope: '#(1)'.", $args['scope']));
        }

        if (isset($args['filepath'])) {
            $configfile = $args['filepath'];
        }
        try {
            $config_php = join('', file($configfile));
            foreach ($args['variables'] as $name => $value) {
                $config_php = preg_replace('/\[\'' . $name . '\'\]\s*=\s*(\'|\")(.*)\\1;/', "['" . $name . "'] = '$value';", $config_php);
            }

            $fp = fopen($configfile, 'wb');
            fwrite($fp, $config_php);
            fclose($fp);
            return true;

        } catch (Exception $e) {
            throw new FileNotFoundException($configfile);
        }
    }

    /**
     * Include a module init file and run a function
     * @see \Xaraya\Modules\Modules\AdminApi::executeinitfunction()
     *
     * @access public
     * @param array<string, mixed> $args array of optional parameters<br/>
     *        string   $args['directory'] the directory to include<br/>
     *        string   $args['initfunc'] init|upgrade|remove|...
     * @return boolean true on success, false on failure
     * @throws EmptyParameterException
     * @throws FileNotFoundException
     */
    public function initialise(array $args = [])
    {
        extract($args);


        if (empty($directory) || empty($initfunc)) {
            throw new EmptyParameterException('directory or initfunc');
        }

        $osDirectory = $this->prep()->path($directory);
        $modInitFile = sys::code() . 'modules/' . $osDirectory . '/xarinit.php';


        // support module Installer classes - see modules_adminapi_executeinitfunction()
        if (!file_exists($modInitFile)) {
            // use modType = 'installer' here to get the module Installer class (if available)
            $modInitFunc = $this->mod()->getModuleClassMethod($directory, 'installer', $initfunc, 'api');
            if (!empty($modInitFunc)) {
                // Note: we don't support upgrade($oldversion) here
                $res = $modInitFunc();
                if ($res == false) {
                    // exception
                    throw new Exception('Core initialization failed for ' . $modInitFunc);
                }
                return true;
            }
            throw new FileNotFoundException($modInitFile);
        }
        sys::import('modules.' . $osDirectory . '.xarinit');

        // Run the function, check for existence

        // @todo support namespaces if not using installer class - see modules_adminapi_executeinitfunction()
        $modInitFunc = $osDirectory . '_' . $initfunc;
        if (function_exists($modInitFunc)) {
            $res = $modInitFunc();

            if ($res == false) {
                // exception
                throw new Exception('Core initialization failed for ' . $modInitFunc);
            }
        } else {
            // modulename_init() not found?!
            throw new FunctionNotFoundException($modInitFunc);
        }

        return true;
    }
}
