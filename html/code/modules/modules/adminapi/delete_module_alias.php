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
use xarConfigVars;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi delete_module_alias function
 * @extends MethodClass<AdminApi>
 */
class DeleteModuleAliasMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * remove an alias for a module name
     * (only used for short URL support at the moment)
     * @author Xaraya Development Team
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['aliasModName'] name of the 'fake' module you want to remove<br/>
     * string   $args['modName'] name of the 'real' module it was assigned to
     * @return bool true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::deleteModuleAlias()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($aliasModName)) {
            throw new EmptyParameterException('aliasModName');
        }

        $aliases = xarConfigVars::get(null, 'System.ModuleAliases');
        if (!isset($aliases[$aliasModName])) {
            return false;
        }
        // don't remove alias if it's already assigned to some other module !
        if ($aliases[$aliasModName] != $modName) {
            return false;
        }
        unset($aliases[$aliasModName]);
        xarConfigVars::set(null, 'System.ModuleAliases', $aliases);

        return true;
    }
}
