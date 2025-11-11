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

/**
 * dynamicdata utilapi export_objectdef function
 * @extends MethodClass<UtilApi>
 */
class ExportObjectdefMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Export an object definition to XML
     * @author mikespub <mikespub@xaraya.com>
     * @param array<string,mixed> $args
     * with
     *     int $args['objectid'] object id of the object to export
     *  string $args['format'] the export format to use (optional)
     *    bool $args['tofile'] save to file (optional)
     * @return string|void
     * @see UtilApi::exportObjectdef()
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

        return \Xaraya\DataObject\Export\DataObjectExporter::export($objectid, null, $format, $tofile);
    }
}
