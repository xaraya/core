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

use Xaraya\DataObject\Export\DataObjectExporter;
use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UtilApi;

/**
 * dynamicdata utilapi export_items function
 * @extends MethodClass<UtilApi>
 */
class ExportItemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Export all object items for an object id to XML
     * @author mikespub <mikespub@xaraya.com>
     * @param array<string,mixed> $args
     * with
     *     int $args['objectid'] object id of the object items to export
     *  string $args['format'] the export format to use (optional)
     *    bool $args['tofile'] save to file (optional)
     * @return string|void
     * @see UtilApi::exportItems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($objectid)) {
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

        $xar = $this->getStaticServices();
        return DataObjectExporter::export($objectid, 'all', $format, $tofile, $xar);
    }
}
