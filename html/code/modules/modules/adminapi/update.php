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
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules adminapi update function
 * @extends MethodClass<AdminApi>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update module information
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['regid'] the id number of the module to update<br/>
     * string   $args['displayname'] the new display name of the module<br/>
     * string   $args['description'] the new description of the module
     * @return bool|void true on success, false on failure
     * @see AdminApi::update()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (!isset($regid)) {
            throw new EmptyParameterException('regid');
        }

        // Security Check
        if (!xarSecurity::check('AdminModules', 0, 'All', "All:All:$regid")) {
            return;
        }

        if (!empty($observers)) {
            foreach ($observers as $hookmod => $subjects) {
                $observer_id = xarMod::getRegID($hookmod);
                if (!$adminapi->updatehooks([
                    'regid' => $observer_id,
                    'subjects' => $subjects,
                ])) {
                    return;
                }
            }
        }

        return true;
    }
}
