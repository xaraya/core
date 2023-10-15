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

sys::import('modules.dynamicdata.class.import.generic');
use Xaraya\DataObject\Import\DataObjectImporter;

/**
 * Import an object definition or an object item from XML
 *
 * @param array<string, mixed> $args
 * with
 *     $args['file'] location of the .xml file containing the object definition, or
 *     $args['xml'] XML string containing the object definition
 *     $args['format'] import format to use (default xml)
 *     $args['prefix'] table prefix for local database installation (default xarDB prefix)
 *     $args['overwrite'] overwrite existing object definition (default false)
 *     $args['keepitemid'] (try to) keep the item id of the different items (default false)
 *     $args['entry'] optional array of external references. (deprecated)
 * @return mixed|void object id on success, null on failure
 */
function dynamicdata_utilapi_import(array $args = [])
{
    $args['file'] ??= null;
    $args['xml'] ??= null;
    $args['format'] ??= 'xml';
    $args['prefix'] ??= xarDB::getPrefix();
    $args['overwrite'] ??= false;
    $args['keepitemid'] ??= false;
    return DataObjectImporter::import($args['file'], $args['xml'], $args['format'], $args['prefix'], $args['overwrite'], $args['keepitemid']);
}
