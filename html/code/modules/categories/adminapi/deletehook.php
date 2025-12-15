<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use BadParameterException;

/**
 * categories adminapi deletehook function
 * @extends MethodClass<AdminApi>
 */
class DeletehookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete linkage for an item - hook for ('item','delete','API')
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return array|void Data array
     * @throws \BadParameterException Thrown if object was not found
     * @see AdminApi::deletehook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        if (!isset($objectid) || !is_numeric($objectid)) {
            $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)', 'object ID', 'admin', 'deletehook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (empty($extrainfo['module'])) {
            $modname = $this->req()->getModule();
        } else {
            $modname = $extrainfo['module'];
        }

        $modid = $this->mod()->getRegID($modname);
        if (empty($modid)) {
            $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'deletehook', 'categories');
            throw new BadParameterException(null, $msg);
        }
        if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
            $itemtype = $extrainfo['itemtype'];
        } else {
            $itemtype = 0;
        }

        if (!$adminapi->unlink(['iid' => $objectid,
            'itemtype' => $itemtype,
            'modid' => $modid])) {
            return;
        }

        // Return the extra info
        return $extrainfo;
    }
}
