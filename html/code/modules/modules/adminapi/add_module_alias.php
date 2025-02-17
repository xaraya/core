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
use DuplicateException;
use EmptyParameterException;
use xarConfigVars;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi add_module_alias function
 * @extends MethodClass<AdminApi>
 */
class AddModuleAliasMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * define a module name as an alias for some other module
     * (only used for short URL support at the moment)
     * @author Xaraya Development Team
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['modName'] name of the 'real' module you want to assign it to<br/>
     * string   $args['aliasModName'] name of the 'fake' module you want to define
     * @return bool|void true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::addModuleAlias()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        if (empty($aliasModName)) {
            throw new EmptyParameterException('aliasModName');
        }

        // Check if the module name we want to define is already in use
        if (xarMod::getBaseInfo($aliasModName)) {
            throw new DuplicateException(['module alias',$aliasModName]);
        } else {
            // We did not find the base info, that is good, no?
        }

        // Check if the alias we want to set it to *does* exist
        if (!xarMod::getBaseInfo($modName)) {
            return;
        }

        // Get the list of current aliases
        $aliases = xarConfigVars::get(null, 'System.ModuleAliases');
        if (!empty($aliases[$aliasModName]) && $aliases[$aliasModName] != $modName) {
            throw new DuplicateException([$aliasModName,$aliases[$aliasModName]], 'Module alias #(1) is already used by module #(2)');
        }

        // the direction is fake module name -> true module, not the reverse !
        $aliases[$aliasModName] = $modName;
        xarConfigVars::set(null, 'System.ModuleAliases', $aliases);

        return true;
    }
}
