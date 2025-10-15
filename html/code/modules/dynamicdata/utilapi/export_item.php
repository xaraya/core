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
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata utilapi export_item function
 * @extends MethodClass<UtilApi>
 */
class ExportItemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Export a single object item for an object id and item id to XML
     * @author mikespub <mikespub@xaraya.com>
     * @param array<string,mixed> $args
     * with
     *     int $args['objectid'] object id of the object item to export
     *     int $args['itemid'] item id of the object item to export
     *  string $args['format'] the export format to use (optional)
     *    bool $args['tofile'] save to file (optional)
     * @return string|void
     * @see UtilApi::exportItem()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($objectid) || empty($itemid)) {
            return;
        }
        if (empty($format)) {
            $format = 'xml';
        }
        if (!empty($tofile)) {
            $tofile = true;
        } else {
            $tofile = false;
        }

        return \Xaraya\DataObject\Export\DataObjectExporter::export($objectid, $itemid, $format, $tofile);
    }
}
