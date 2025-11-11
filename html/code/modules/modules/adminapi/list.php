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

/**
 * modules adminapi list function
 * @extends MethodClass<AdminApi>
 */
class ListMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Obtain list of modules (deprecated)
     * @author Xaraya Development Team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array|void the known modules
     * @see AdminApi::list()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get arguments from argument array
        extract($args);

        // Security Check
        if (!$this->sec()->checkAccess('AdminModules')) {
            return;
        }

        // Obtain information
        if (!isset($state)) {
            $state = '';
        }
        $modList = $adminapi->getlist(['filter'     => ['State' => $state]]);
        //throw back
        if (!isset($modList)) {
            return;
        }

        return $modList;
    }
}
