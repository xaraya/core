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
use DataObjectImporter;
use xarDB;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata utilapi import function
 * @extends MethodClass<UtilApi>
 */
class ImportMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import an object definition or an object item from XML
     * @param array<string,mixed> $args
     * with
     *     $args['file'] location of the .xml file containing the object definition, or
     *     $args['xml'] XML string containing the object definition
     *     $args['format'] import format to use (default xml)
     *     $args['prefix'] table prefix for local database installation (default xarDB prefix)
     *     $args['overwrite'] overwrite existing object definition (default false)
     *     $args['keepitemid'] (try to) keep the item id of the different items (default false)
     *     $args['entry'] optional array of external references. (deprecated)
     * @return mixed|void object id on success, null on failure
     * @see UtilApi::import()
     */
    public function __invoke(array $args = [])
    {
        $args['file'] ??= null;
        $args['xml'] ??= null;
        $args['format'] ??= 'xml';
        $args['prefix'] ??= $this->db()->getPrefix();
        $args['overwrite'] ??= false;
        $args['keepitemid'] ??= false;
        return \Xaraya\DataObject\Import\DataObjectImporter::import($args['file'], $args['xml'], $args['format'], $args['prefix'], $args['overwrite'], $args['keepitemid']);
    }
}
