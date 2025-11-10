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
use BadParameterException;
use EmptyParameterException;
use xarHooks;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi enablehooks function
 * @extends MethodClass<AdminApi>
 */
class EnablehooksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Enable hooks between a caller module and a hook module
     * Note : hooks will be enabled for all item types if no specific item type is given
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['callerModName'] caller module<br/>
     * string   $args['callerItemType'] optional item type for the caller module<br/>
     * string   $args['hookModName'] hook module
     * @return bool true on success, false on failure
     * @throws \EmptyParameterException
     * @see AdminApi::enablehooks()
     */
    public function __invoke(array $args = [])
    {
        // Security Check (called by other modules, so we can't use one this here)
        //    if(!$this->sec()->checkAccess('ManageModules')) return;

        // Get arguments from argument array
        extract($args);

        // Argument check
        if (empty($callerModName)) {
            throw new EmptyParameterException('callerModName');
        }
        if (empty($hookModName)) {
            throw new EmptyParameterException('hookModName');
        }

        // CHECKME: don't allow hooking to yourself !?
        if ($callerModName == $hookModName) {
            // <chris> this is allowed, for now (eg, roles usermenu > roles)
            //throw new BadParameterException('hookModName');
        }

        if (empty($callerItemType)) {
            $callerItemType = 0;
        }

        return $this->hooked()->attach($hookModName, $callerModName, $callerItemType);

    }
}
