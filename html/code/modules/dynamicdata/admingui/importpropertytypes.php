<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminGui;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminGui;
use Xaraya\Modules\DynamicData\AdminApi;
use xarMod;
use xarSecurity;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata admin importpropertytypes function
 * @extends MethodClass<AdminGui>
 */
class ImportpropertytypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a property type
     * @return array|void empty array for the template display
     * @see AdminGui::importpropertytypes()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        $args['flush'] = 'false';
        $success = $adminapi->importpropertytypes($args);

        return [];
    }
}
