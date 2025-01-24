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
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin update_propertydefs function
 * @extends MethodClass<AdminGui>
 */
class UpdatePropertydefsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update configuration parameters of the module
     * This is a standard function to update the configuration parameters of the
     * module given the information passed back by the modification form
     * @return bool|string|void and redirect to view_propertydefs
     * @see AdminGui::updatePropertydefs()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!$this->var()->check('flushPropertyCache', $flushPropertyCache)) {
            return;
        }

        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        if (isset($flushPropertyCache) && ($flushPropertyCache == true)) {
            $args['flush'] = 'true';
            if ($adminapi->importpropertytypes($args)) {
                $this->ctl()->redirect($this->mod()->getURL('admin', 'view_propertydefs'));
                return true;
            } else {
                return 'Unknown error while clearing and reloading Property Definition Cache.';
            }
        }

        $this->ctl()->redirect($this->mod()->getURL('admin', 'view_propertydefs'));
        return true;
    }
}
