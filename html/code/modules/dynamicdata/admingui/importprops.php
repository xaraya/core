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
use Xaraya\Modules\DynamicData\UtilApi;
use EmptyParameterException;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin importprops function
 * @extends MethodClass<AdminGui>
 */
class ImportpropsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import the dynamic properties for a module + itemtype from a static table
     * @todo use context
     * @see AdminGui::importprops()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        if (!$this->var()->check('objectid', $objectid)) {
            return;
        }
        if (!$this->var()->check('module_id', $module_id)) {
            return;
        }
        if (!$this->var()->check('itemtype', $itemtype)) {
            return;
        }
        if (!$this->var()->check('table', $table)) {
            return;
        }

        if (empty($module_id)) {
            throw new EmptyParameterException('module_id');
        }

        // Confirm authorisation code.  This checks that the form had a valid
        // authorisation code attached to it.  If it did not then the function will
        // proceed no further as it is possible that this is an attempt at sending
        // in false data to the system
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        if (!$utilapi->importproperties(['module_id' => $module_id,
                'itemtype' => $itemtype,
                'table' => $table,
                'objectid' => $objectid]
        )) {
            return;
        }

        $this->ctl()->redirect($this->mod()->getURL(
            'admin',
            'modifyprop',
            ['module_id' => $module_id,
                'itemtype' => $itemtype]
        ));
    }
}
