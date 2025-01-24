<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use Xaraya\DataObject\AdminApi;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin importpropertytypes function
 * @extends MethodClass<AdminGui>
 */
class ImportpropertytypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a property type
     * @package modules\dynamicdata
     * @subpackage dynamicdata
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/182.html
     * @author mikespub <mikespub@xaraya.com>
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
