<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

sys::import('modules.dynamicdata.class.export.generic');
use Xaraya\DataObject\Export\DataObjectExporter;

/**
 * Export all object items for an object id to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 * @param array<string, mixed> $args
 * with
 *     int $args['objectid'] object id of the object items to export
 *  string $args['format'] the export format to use (optional)
 * @return string|void
 */
function dynamicdata_utilapi_export_items(array $args = [])
{
    extract($args);

    if (empty($objectid)) {
        return;
    }
    if (empty($format)) {
        $format = 'xml';
    }

    return DataObjectExporter::export($objectid, 'all', $format);
}
