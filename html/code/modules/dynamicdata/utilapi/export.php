<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UtilApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UtilApi;
use xarMod;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata utilapi export function
 * @extends MethodClass<UtilApi>
 */
class ExportMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Export an object definition or an object item to XML
     * @author mikespub <mikespub@xaraya.com>
     * @param array<string,mixed> $args
     * with
     *     object $args['objectref'] reference to the object to export, or
     *        int $args['objectid'] object id of the object to export
     *      mixed $args['itemid'] item id or 'all' of the item(s) you want to import (optional)
     *        int $args['module_id'] module id of the object to export (deprecated)
     *        int $args['itemtype'] item type of the object to export (deprecated)
     *     string $args['format'] the export format to use (optional)
     *       bool $args['tofile'] save to file (optional)
     * @return string|void
     * @see UtilApi::export()
     */
    public function __invoke(array $args = [])
    {
        /** @var UtilApi $utilapi */
        $utilapi = $this->utilapi();
        if (isset($args['objectref'])) {
            $objectid = $args['objectref']->objectid;
            $itemid = null;
            $format = $args['format'] ?? 'xml';
            $tofile = $args['tofile'] ?? false;
        } else {
            extract($args);

            if (empty($objectid)) {
                $objectid = null;
            }
            // if (empty($module_id)) {
            //     $module_id = $this->mod()->getRegID('dynamicdata');
            // }
            // if (empty($itemtype)) {
            //     $itemtype = 0;
            // }
            if (empty($itemid)) {
                $itemid = null;
            }
        }
        if (!isset($objectid) && isset($itemid)) {
            $objectid = $itemid;
            $itemid = null;
        }
        if (empty($format)) {
            $format = 'xml';
        }
        if (!empty($tofile)) {
            $tofile = true;
        } else {
            $tofile = false;
        }

        if (empty($objectid)) {
            return;
        }

        if (!empty($itemid)) {
            if (is_numeric($itemid)) {
                return $utilapi->exportItem(['objectid' => $objectid, 'itemid' => $itemid, 'format' => $format, 'tofile' => $tofile]);
            }
            return $utilapi->exportItems(['objectid' => $objectid, 'format' => $format, 'tofile' => $tofile]);
        }
        return $utilapi->exportObjectdef(['objectid' => $objectid, 'format' => $format, 'tofile' => $tofile]);
    }
}
